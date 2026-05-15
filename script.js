(function() {
  var data = razorpay_wc_checkout_vars;
  if(data === 'checkoutForm' || data[0] === 'checkoutForm') {
    document.getElementById("checkoutForm").submit();
  } else if(data === 'routeAnalyticsForm'){
    document.getElementById("routeAnalyticsForm").submit();
  } else {
    var setDisabled = function(id, state) {
      if (typeof state === 'undefined') {
        state = true;
      }
      var elem = document.getElementById(id);
      if (elem) {
          if (state === false) {
            elem.removeAttribute('disabled');
          } else {
            elem.setAttribute('disabled', state);
          }
      }
    };

    // Modified by Razorpay Popup Dismiss Fix Add-on
    data.modal = {
      ondismiss: function() {
        setDisabled('btn-razorpay', false);
        
        // Check if payment was already successful
        var paymentIdField = document.getElementById('razorpay_payment_id');
        if (paymentIdField && paymentIdField.value) {
            return;
        }

        // Trigger cancellation flow to ensure backend status is updated
        var cancelBtn = document.getElementById('btn-razorpay-cancel');
        var form = document.forms['razorpayform'];

        if (form) {
            // Ensure we are sending the form-submit flag so backend knows it's a cancellation
            var submitFlag = document.createElement('input');
            submitFlag.type = 'hidden';
            submitFlag.name = 'razorpay_wc_form_submit';
            submitFlag.value = '1';
            form.appendChild(submitFlag);
            form.submit();
        } else if (cancelBtn) {
            cancelBtn.click();
        } else if (data.cancel_url) {
            // Ultimate fallback: redirect to checkout
            window.location.href = data.cancel_url;
        }
      },
    };

    data.handler = function(payment) {
      setDisabled('btn-razorpay-cancel');
      var successMsg = document.getElementById('msg-razorpay-success');
      if (successMsg) successMsg.style.display = 'block';
      
      document.getElementById('razorpay_payment_id').value =
        payment.razorpay_payment_id;
      document.getElementById('razorpay_signature').value =
        payment.razorpay_signature;
      document.razorpayform.submit();
    };

    var razorpayCheckout = new Razorpay(data);

    // global method
    function openCheckout() {
      // Disable the pay button
      setDisabled('btn-razorpay');
      razorpayCheckout.open();
    }

    function addEvent(element, evnt, funct) {
      if (!element) return;
      if (element.attachEvent) {
        return element.attachEvent('on' + evnt, funct);
      } else return element.addEventListener(evnt, funct, false);
    }

    if (document.readyState === 'complete') {
      addEvent(document.getElementById('btn-razorpay'), 'click', openCheckout);
      openCheckout();
    } else {
      document.addEventListener('DOMContentLoaded', function() {
        addEvent(document.getElementById('btn-razorpay'), 'click', openCheckout);
        openCheckout();
      });
    }
  }
})();
