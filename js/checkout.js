/**
 * Hooaij Checkout JS
 * Loaded only on product pages: Tachoscope, Home Choice Security, PES
 * Handles: variant selection, plan selection, phone combination, PayPal SDK init, receipt rendering
 */
(function ($) {
  "use strict";

  // ─── State ─────────────────────────────────────────────────────────
  var activeCheckoutForm = null;

  // ─── Utility: Combine phone number ─────────────────────────────────
  function buildFullPhone($form) {
    var countryCode = $form.find(".country-code-select").val() || "+234";
    var number = $form
      .find(".phone-number-input")
      .val()
      .trim()
      .replace(/^0+/, "");
    var full = countryCode + number;
    $form.find(".checkout-phone-full").val(full);
    return full;
  }

  // ─── Utility: Show error ────────────────────────────────────────────
  function showError($form, message) {
    $form.find(".checkout-error-message").text(message);
    $form.find(".checkout-error").slideDown(200);
  }

  function hideError($form) {
    $form.find(".checkout-error").slideUp(200);
  }

  // ─── Utility: Validate form ─────────────────────────────────────────
  function validateForm($form) {
    hideError($form);
    var name = ($form.find(".checkout-name").val() || "").trim();
    var email = ($form.find(".checkout-email").val() || "").trim();
    var phone = ($form.find(".phone-number-input").val() || "").trim();
    var addressEl = $form.find(".checkout-address");
    var address = addressEl.length ? addressEl.val().trim() : "";
    var orderType = $form.data("type");

    if (!name) {
      showError($form, "Full name is required.");
      return false;
    }
    if (!email || !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
      showError($form, "A valid email address is required.");
      return false;
    }
    if (!phone) {
      showError($form, "Phone number is required.");
      return false;
    }
    if (orderType === "tachoscope" && !address) {
      showError($form, "Shipping address is required for device orders.");
      return false;
    }
    return true;
  }

  // ─── Utility: Get USD total from form ──────────────────────────────
  function getUsdTotal($form) {
    var baseUsd = parseFloat($form.data("price-usd")) || 0;
    var qty = parseInt($form.find(".checkout-qty").val()) || 1;
    var orderType = $form.data("type");
    if (orderType === "tachoscope") {
      return Math.round(baseUsd * qty * 100) / 100;
    }
    return baseUsd;
  }

  // ─── Utility: Format USD ────────────────────────────────────────────
  function formatUsd(amount) {
    return "$" + parseFloat(amount).toFixed(2);
  }

  // ─── Update price display ───────────────────────────────────────────
  function updatePriceDisplay($form) {
    var total = getUsdTotal($form);
    var baseNgn = parseFloat($form.data("price-ngn")) || 0;
    var qty = parseInt($form.find(".checkout-qty").val()) || 1;
    var rate = parseFloat(hooaijCheckout.exchangeRate) || 1400;
    var totalNgn = baseNgn * qty;

    $form.find(".checkout-price-display").text(formatUsd(total));

    $form
      .find(".checkout-price-summary small")
      .text(
        "≈ ₦" +
          totalNgn.toLocaleString() +
          " @ ₦" +
          rate.toLocaleString() +
          "/$1",
      );
  }

  // ─── Initialize PayPal for a form ──────────────────────────────────
  function initPayPalButtons($form) {
    var sku = $form.data("sku");
    var containerId = "paypal-button-container-" + sku;
    var $container = $("#" + containerId);

    if (!$container.length) return;
    if (!window.paypal) {
      $container.html(
        '<p style="color:#e74c3c; font-size:13px;">⚠ PayPal SDK not loaded. Please check your API key in Settings.</p>',
      );
      return;
    }

    $container.empty();

    try {
      paypal
        .Buttons({
          style: {
            layout: "vertical",
            color: "gold",
            shape: "pill",
            label: "pay",
            height: 48,
          },

          createOrder: function (data, actions) {
            if (!validateForm($form)) {
              return Promise.reject(new Error("Validation failed"));
            }
            buildFullPhone($form);
            var total = getUsdTotal($form);
            var orderParams = {
              purchase_units: [
                {
                  amount: {
                    value: total.toFixed(2),
                    currency_code: "USD",
                  },
                  description: $form.find(".checkout-sku").val(),
                },
              ],
              application_context: {
                shipping_preference: "NO_SHIPPING",
              },
            };

            return actions.order.create(orderParams);
          },

          onApprove: function (data, actions) {
            showProcessingState($form);
            return actions.order.capture().then(function (details) {
              var formData = {
                action: "hooaij_process_order",
                nonce: hooaijCheckout.nonce,
                customer_name: (
                  $form.find(".checkout-name").val() || ""
                ).trim(),
                customer_email: (
                  $form.find(".checkout-email").val() || ""
                ).trim(),
                customer_phone: $form.find(".checkout-phone-full").val(),
                shipping_address: $form.find(".checkout-address").length
                  ? $form.find(".checkout-address").val().trim()
                  : "",
                sku: $form.find(".checkout-sku").val(),
                plan_tier: $form.find(".checkout-plan-tier").val(),
                quantity: $form.find(".checkout-qty").length
                  ? parseInt($form.find(".checkout-qty").val())
                  : 1,
                paypal_order_id: data.orderID,
              };

              $.post(hooaijCheckout.ajaxUrl, formData)
                .done(function (response) {
                  if (response.success) {
                    renderReceipt($form, response.data);
                  } else {
                    hideProcessingState($form);
                    showError(
                      $form,
                      response.data.message ||
                        "Order processing failed. Please contact support.",
                    );
                  }
                })
                .fail(function () {
                  hideProcessingState($form);
                  showError(
                    $form,
                    "A network error occurred. Please check your connection and contact support with PayPal ref: " +
                      data.orderID,
                  );
                });
            });
          },

          onError: function (err) {
            showError(
              $form,
              "Payment was not completed. Please try again or use a different payment method.",
            );
          },

          onCancel: function () {
            hideError($form);
          },
        })
        .render("#" + containerId);
    } catch (e) {
      $container.html(
        '<p style="color:#e74c3c; font-size:13px;">⚠ Could not load payment buttons: ' +
          e.message +
          "</p>",
      );
    }
  }

  // ─── Processing State ───────────────────────────────────────────────
  /**
   * Show a professional processing overlay.
   */
  function showProcessingState($form) {
    // Prevent double overlays
    if ($form.find(".checkout-processing-overlay").length) return;

    var overlayHtml =
      '<div class="checkout-processing-overlay">' +
      '<i class="fas fa-circle-notch fa-spin"></i>' +
      "<p>Payment Successful!</p>" +
      "<span>Verifying details and generating your receipt...</span>" +
      "</div>";

    $form.prepend(overlayHtml);

    // Also scroll slightly to the form if not in view
    $("html, body").animate({ scrollTop: $form.offset().top - 120 }, 300);
  }

  /**
   * Hide the processing overlay.
   */
  function hideProcessingState($form) {
    $form.find(".checkout-processing-overlay").fadeOut(300, function () {
      $(this).remove();
    });
  }

  // ─── Render Receipt ─────────────────────────────────────────────────
  function renderReceipt($form, data) {
    var isSubscription = data.order_type === "subscription";
    var codeHtml = "";
    if (isSubscription && data.unique_code) {
      codeHtml =
        '<div style="background:#121212; border-radius:8px; padding:20px; text-align:center; margin:20px 0;">' +
        '<p style="color:rgba(255,255,255,0.6); font-size:12px; letter-spacing:1px; text-transform:uppercase; margin:0 0 8px;">Your Activation Code</p>' +
        '<span style="color:#e57825; font-family:monospace; font-size:26px; font-weight:700; letter-spacing:5px; display:block;">' +
        data.unique_code +
        "</span>" +
        '<small style="color:rgba(255,255,255,0.4); font-size:11px;">Keep this code safe — needed for service activation</small>' +
        "</div>";
    }

    var nextStepHtml = isSubscription
      ? '<div class="info-box" style="margin-top:15px;"><strong>Next Steps:</strong> A Hooaij representative will contact you shortly to arrange installation and activate your service.</div>'
      : '<div class="info-box" style="margin-top:15px;"><strong>Delivery Update:</strong> Our installation team will contact you to arrange delivery. A confirmation email has been sent to ' +
        data.order_date +
        ".</div>";

    var receiptHtml =
      '<div class="receipt-card">' +
      '<div class="receipt-icon">✅</div>' +
      '<h3 style="color:var(--dark); margin:0 0 5px; font-size:1.5rem;">Payment Confirmed!</h3>' +
      '<p style="color:var(--text-muted); margin:0 0 5px;">Your order has been placed successfully</p>' +
      '<p class="receipt-no">Receipt: <strong>' +
      data.receipt_no +
      "</strong></p>" +
      '<div style="background:var(--light); border-radius:var(--border-radius); padding:15px 20px; margin:20px 0; text-align:left;">' +
      '<div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed var(--border-color); font-size:0.9rem;"><span style="font-weight:600;">Product</span><span>' +
      data.product_name +
      "</span></div>" +
      '<div style="display:flex; justify-content:space-between; padding:6px 0; font-size:0.9rem;"><span style="font-weight:600;">Amount Paid</span><span style="color:var(--primary); font-weight:700;">$' +
      data.amount_usd +
      "</span></div>" +
      "</div>" +
      codeHtml +
      nextStepHtml +
      '<p style="margin-top:20px; font-size:0.85rem; color:var(--text-muted);">A confirmation email has been sent to your inbox.</p>' +
      "</div>";

    $form.slideUp(300, function () {
      $form.replaceWith($(receiptHtml).hide().slideDown(400));
    });
  }

  // ─── Plan Card Selection (Subscriptions) ───────────────────────────
  function initPlanCards() {
    $(document).on("click", ".plan-select-btn", function (e) {
      e.preventDefault();
      var $btn = $(this);
      var $card = $btn.closest(".plan-card");
      var $plansContainer = $btn.closest(".pricing-plans");
      var sku = $btn.data("sku");
      var planTier = $btn.data("plan-tier") || "";

      // Highlight selected card
      $plansContainer
        .find(".plan-card")
        .removeClass("selected")
        .css("opacity", "0.6");
      $card.addClass("selected").css("opacity", "1");

      // Hide all checkout panels in this section
      $plansContainer
        .closest(".product-order-section")
        .find(".checkout-panel")
        .removeClass("active")
        .slideUp(200);

      // Show the right checkout panel
      var $targetForm = $("#checkout-form-" + sku);
      if ($targetForm.length) {
        $targetForm.addClass("active").slideDown(300, function () {
          // Init PayPal buttons if not already rendered
          if (
            $("#paypal-button-container-" + sku).children().length === 0 ||
            $("#paypal-button-container-" + sku + " .paypal-loading").length
          ) {
            initPayPalButtons($targetForm);
          }
        });
        activeCheckoutForm = $targetForm;
        $("html, body").animate(
          { scrollTop: $targetForm.offset().top - 80 },
          400,
        );
      }
    });
  }

  // ─── Variant Selector (Tachoscope) ─────────────────────────────────
  function initVariantSelector() {
    // Vehicle type → populate variant dropdown
    $("#tachoscope-vehicle-type").on("change", function () {
      var prefix = $(this).val();
      var products =
        (window.hooaijTachoProducts && window.hooaijTachoProducts[prefix]) ||
        [];
      var $variantSelect = $("#tachoscope-variant");
      var $variantGroup = $("#tachoscope-variant-group");

      // Clear and repopulate variant dropdown
      $variantSelect.html('<option value="">— Choose a variant —</option>');
      $.each(products, function (i, p) {
        $variantSelect.append(
          $("<option>", {
            value: p.sku,
            text: p.name,
            "data-price-ngn": p.price_ngn,
            "data-price-usd": p.price_usd,
            "data-features": JSON.stringify(p.features || []),
            "data-description": p.description || "",
            "data-length": p.length_cm || "",
            "data-width": p.width_cm || "",
            "data-height": p.height_cm || "",
            "data-weight": p.weight_kg || "",
          }),
        );
      });

      // Show or hide the variant group based on whether options exist
      if (products.length) {
        $variantGroup.slideDown(250);
      } else {
        $variantGroup.slideUp(200);
      }

      // Hide description and action box until a variant is chosen
      $("#tacho-selected-description").removeClass("visible").text("");
      $("#tachoscope-action-box").slideUp(200);

      // Reset variant select to placeholder
      $variantSelect.val("");
    });

    // Variant → update price, description and checkout form
    $("#tachoscope-variant").on("change", function () {
      var $selected = $(this).find(":selected");
      var sku = $(this).val();
      if (!sku) return;

      var priceNgn = parseFloat($selected.data("price-ngn")) || 0;
      var priceUsd = parseFloat($selected.data("price-usd")) || 0;
      var features = $selected.data("features") || [];
      var description = $selected.data("description") || "";
      var length = $selected.data("length") || "";
      var width = $selected.data("width") || "";
      var height = $selected.data("height") || "";
      var weight = $selected.data("weight") || "";
      var rate = parseFloat(hooaijCheckout.exchangeRate) || 1400;

      // Try parsing features if it came back as a string
      if (typeof features === "string") {
        try {
          features = JSON.parse(features);
        } catch (e) {
          features = [];
        }
      }

      // Update description reveal
      if (description) {
        $("#tacho-selected-description").text(description).addClass("visible");
      } else {
        $("#tacho-selected-description").removeClass("visible").text("");
      }

      // Update price display
      if (hooaijCheckout.displayCurrency === "NGN") {
        $("#tachoscope-price-display").text("₦" + priceNgn.toLocaleString());
      } else {
        $("#tachoscope-price-display").text("$" + priceUsd.toFixed(2));
      }
      $("#tachoscope-ngn-equiv").text(
        "≈ ₦" +
          priceNgn.toLocaleString() +
          " @ ₦" +
          rate.toLocaleString() +
          "/$1",
      );

      // Update specs
      if (length) {
        $("#tachoscope-specs").show();
        $("#spec-length").text(length + " cm");
        $("#spec-width").text(width + " cm");
        $("#spec-height").text(height + " cm");
        $("#spec-weight").text(weight + " kg");
      }

      // Update features list
      if (features.length) {
        var featureHtml = features
          .map(function (f) {
            return (
              '<li><i class="fas fa-check-circle" style="color:var(--primary); margin-right:8px;"></i>' +
              f +
              "</li>"
            );
          })
          .join("");
        $("#tachoscope-features-list").html(featureHtml);
      }

      // Update checkout form data attributes
      var $checkoutForm = $('.hooaij-checkout-form[data-type="tachoscope"]');
      $checkoutForm
        .data("sku", sku)
        .attr("data-sku", sku)
        .data("price-ngn", priceNgn)
        .attr("data-price-ngn", priceNgn)
        .data("price-usd", priceUsd)
        .attr("data-price-usd", priceUsd);
      $checkoutForm.find(".checkout-sku").val(sku);

      // Show action box and price box
      $("#tachoscope-price-box").show();
      $("#tachoscope-action-box").slideDown(250);
      updatePriceDisplay($checkoutForm);

      // Update the PayPal container ID for the new SKU
      var newBtnId = "paypal-button-container-" + sku;
      $checkoutForm.find(".paypal-btn-container").attr("id", newBtnId).empty();

      // Only re-init PayPal if the checkout wrapper is already visible
      if ($("#tachoscope-checkout-wrapper").is(":visible")) {
        initPayPalButtons($checkoutForm);
      }
    });
  }

  // ─── Quantity change updates price ─────────────────────────────────
  function initQuantityChange() {
    $(document).on("change input", ".checkout-qty", function () {
      var $form = $(this).closest(".hooaij-checkout-form");
      updatePriceDisplay($form);
    });
  }

  // ─── Phone field live update ────────────────────────────────────────
  function initPhoneField() {
    $(document).on(
      "change",
      ".country-code-select, .phone-number-input",
      function () {
        var $form = $(this).closest(".hooaij-checkout-form");
        buildFullPhone($form);
      },
    );
  }

  // ─── Auto-init checkout forms on load ──────────────────────────────
  function initAllCheckoutForms() {
    // Only init forms that are already visible (active panels)
    $(".hooaij-checkout-form.checkout-panel.active").each(function () {
      initPayPalButtons($(this));
    });
  }

  // ─── Dynamic Plan Selector (PES/HCSS V2) ─────────────────────────────
  function initDynamicPlanSelector() {
    $(document).on("change", 'input[name="plan_selection"]', function () {
      var $input = $(this);
      var $label = $input.closest(".plan-radio-item");
      var sku = $input.val();
      var tier = $label.data("tier");
      var priceUsd = parseFloat($input.data("price-usd")) || 0;
      var priceNgn = parseFloat($input.data("price-ngn")) || 0;
      var features = $input.data("features") || [];
      var name = $input.data("name") || "";

      // 1. Update active class
      $(".plan-radio-item").removeClass("active");
      $label.addClass("active");

      // 2. Update price display on button
      $("#current-price-display").text("$" + priceUsd.toFixed(2));

      // 3. Update dynamic features list
      if (features.length) {
        var featureHtml = features
          .map(function (f) {
            return '<li><i class="fas fa-check-circle"></i>' + f + "</li>";
          })
          .join("");
        $("#features-list-target").html(featureHtml);
      }

      // 4. Update the actual checkout form hidden data
      var $form = $("#unified-checkout-wrapper .hooaij-checkout-form");
      if ($form.length) {
        $form
          .data("sku", sku)
          .attr("data-sku", sku)
          .data("price-usd", priceUsd)
          .attr("data-price-usd", priceUsd)
          .data("price-ngn", priceNgn)
          .attr("data-price-ngn", priceNgn)
          .data("plan", tier)
          .attr("data-plan", tier);

        $form.find(".checkout-sku").val(sku);
        $form.find(".checkout-plan-tier").val(tier);

        // Update the form's internal price display labels
        $form.find(".checkout-price-display").text("$" + priceUsd.toFixed(2));
        var rate = parseFloat(hooaijCheckout.exchangeRate) || 1400;
        $form
          .find(".checkout-price-summary small")
          .text(
            "≈ ₦" +
              priceNgn.toLocaleString() +
              " @ ₦" +
              rate.toLocaleString() +
              "/$1",
          );

        // Update the PayPal container ID for the new SKU
        var newBtnId = "paypal-button-container-" + sku;
        $form.find(".paypal-btn-container").attr("id", newBtnId).empty();

        // Only re-init PayPal immediately if the wrapper is already visible
        if ($("#unified-checkout-wrapper").is(":visible")) {
          initPayPalButtons($form);
        }
      }
    });

    // Trigger initial state
    $('input[name="plan_selection"]:checked').trigger("change");

    // Handle "Order Now" trigger button
    $(document).on("click", "#trigger-checkout-btn", function (e) {
      e.preventDefault();
      var $wrapper = $("#unified-checkout-wrapper");
      var $form = $wrapper.find(".hooaij-checkout-form");

      if ($wrapper.is(":visible")) {
        $wrapper.slideUp(300);
      } else {
        // Ensure the inner form is active
        if ($form.length) {
          $form.addClass("active");
        }

        $wrapper.slideDown(400, function () {
          // Init PayPal if not already rendered
          if (
            $form.length &&
            $form.find(".paypal-btn-container").children().length === 0
          ) {
            initPayPalButtons($form);
          }
          $("html, body").animate(
            { scrollTop: $wrapper.offset().top - 100 },
            500,
          );
        });
      }
    });
  }

  // ─── PES Institution + Tier Selector ───────────────────────────────
  function initPesSelector() {
    var $institutionSelect = $("#pes-institution");
    var $tierSelect = $("#pes-tier");
    var $descEl = $("#pes-selected-description");

    if (!$institutionSelect.length || !$tierSelect.length) return;

    function updatePesPlan() {
      var institution = $institutionSelect.val();
      var tier = $tierSelect.val();
      if (!institution || !tier) return;

      var sku = "PES-" + institution + "-" + tier;
      var products = window.hooaijPesProducts || {};
      var product = products[sku];
      if (!product) return;

      var priceUsd = parseFloat(product.price_usd) || 0;
      var priceNgn = parseFloat(product.price_ngn) || 0;
      var features = product.features || [];
      var description = product.description || "";
      var rate = parseFloat(hooaijCheckout.exchangeRate) || 1400;

      // Update description reveal
      if (description) {
        $descEl.text(description).addClass("visible");
      } else {
        $descEl.removeClass("visible").text("");
      }

      // Update price display button
      $("#current-price-display").text("$" + priceUsd.toFixed(2));

      // Update features list
      if (features.length) {
        var featureHtml = features
          .map(function (f) {
            return '<li><i class="fas fa-check-circle"></i>' + f + "</li>";
          })
          .join("");
        $("#features-list-target").html(featureHtml);
      }

      // Update checkout form
      var $form = $("#unified-checkout-wrapper .hooaij-checkout-form");
      if ($form.length) {
        var tierLower = tier.toLowerCase();
        $form
          .data("sku", sku)
          .attr("data-sku", sku)
          .data("price-usd", priceUsd)
          .attr("data-price-usd", priceUsd)
          .data("price-ngn", priceNgn)
          .attr("data-price-ngn", priceNgn)
          .data("plan", tierLower)
          .attr("data-plan", tierLower);

        $form.find(".checkout-sku").val(sku);
        $form.find(".checkout-plan-tier").val(tierLower);
        $form.find(".checkout-price-display").text("$" + priceUsd.toFixed(2));
        $form
          .find(".checkout-price-summary small")
          .text(
            "≈ ₦" +
              priceNgn.toLocaleString() +
              " @ ₦" +
              rate.toLocaleString() +
              "/$1",
          );

        // Update PayPal container ID
        var newBtnId = "paypal-button-container-" + sku;
        $form.find(".paypal-btn-container").attr("id", newBtnId).empty();

        // Only re-init PayPal if checkout is already open
        if ($("#unified-checkout-wrapper").is(":visible")) {
          initPayPalButtons($form);
        }
      }
    }

    $institutionSelect.on("change", updatePesPlan);
    $tierSelect.on("change", updatePesPlan);

    // Trigger on load if values are pre-selected
    if ($institutionSelect.val() && $tierSelect.val()) {
      updatePesPlan();
    }
  }

  // ─── DOM Ready ─────────────────────────────────────────────────────
  $(document).ready(function () {
    initPlanCards();
    initDynamicPlanSelector();
    initVariantSelector();
    initPesSelector();
    initQuantityChange();
    initPhoneField();
    initAllCheckoutForms();

    // Tachoscope: Show checkout form on "Order Now" click
    $(document).on("click", "#tachoscope-order-btn", function (e) {
      e.preventDefault();
      var $btn = $(this);
      var $wrapper = $("#tachoscope-checkout-wrapper");
      var $form = $('.hooaij-checkout-form[data-type="tachoscope"]');

      if ($wrapper.is(":visible")) {
        $wrapper.slideUp(300);
      } else if ($form.length) {
        $form.addClass("active");
        $wrapper.slideDown(300, function () {
          // Init PayPal only if not already rendered
          if ($form.find(".paypal-btn-container").children().length === 0) {
            initPayPalButtons($form);
          }
          $("html, body").animate(
            { scrollTop: $wrapper.offset().top - 80 },
            400,
          );
        });
      }
    });
  });
})(jQuery);
