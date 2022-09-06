<label class="mt-3">Detalles de las tarjetas:</label>
<div class="form-group row">
    <div class="col-5">
        <input class="form-control" type="text" id="cardNumber" data-checkout="cardNumber" placeholder="Nro tarjeta">
    </div>
    <div class="col-sm-2">
        <input type="text" data-checkout="securityCode" class="form-control" placeholder="CVC">
    </div>

    <div class="col-sm-2">
        <input type="text" data-checkout="cardExpirationMonth" class="form-control" placeholder="MM">
    </div>
    <div class="col-sm-2">
        <input type="text" data-checkout="cardExpirationYear" class="form-control" placeholder="AA">
    </div>
</div>

<div class="form-group row">
    <div class="col-5">
        <input type="text" class="form-control" data-checkout="cardholderName" placeholder="Nombre">
    </div>
    <div class="col-5">
        <input type="email" class="form-control" data-checkout="cardholderEmail" placeholder="email@ejemplo.com"
               name="email">
    </div>
</div>
<div class="form-group row">
    <div class="col-2">
        <select id="" class="form-select" data-checkout="docType"></select>
    </div>
    <div class="col-3">
        <input type="text" class="form-control" data-checkout="docNumber" placeholder="Documento">
    </div>
</div>
<div class="form-group row">
    <div class="col">
        <small class="form-text text-mute" role="alert">
            Su pago será convertido a {{ strtoupper(config('services.mercadopago.base_currency')) }}
        </small>
    </div>
</div>

<div class="form-group row">
    <div class="col">
        <small class="form-text text-danger" id="paymentErrors" role="alert">

        </small>
    </div>
</div>

<input type="hidden" id="cardNetwork" name="card_network">
<input type="hidden" id="cardToken" name="card_token">


@push('scripts')
    <script src="https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js"></script>
    <script>


        const mp = window.Mercadopago;

        mp.setPublishableKey('{{config('services.mercadopago.key')}}');
        mp.getIdentificationTypes();



    </script>

    <script>
            function setCardNetwork(){
                let cardNumber = document.getElementById("cardNumber")
                console.log('data ' + cardNumber.value.toString().replace(" ", "").substring(0,6));
                mp.getPaymentMethod(
                    { "bin": cardNumber.value.toString().replace(" ", "").substring(0,6) },
                    function(status, response) {
                        const cardNetwork = document.getElementById("cardNetwork");
                        cardNetwork.value = response[0].id;
                    }
                );
            }
    </script>

    <script>
        const mercadoPagoForm = document.getElementById("paymentForm");
        mercadoPagoForm.addEventListener('submit', function(e) {
            if (mercadoPagoForm.elements.payment_platform.value === "{{ $paymentPlatform->id }}") {
                e.preventDefault();
                setCardNetwork();
                mp.createToken(mercadoPagoForm, function(status, response) {
                    if (status !== 200 && status !== 201) {
                        const errors = document.getElementById("paymentErrors");
                        errors.textContent = response.cause[0].description;
                    } else {
                        const cardToken = document.getElementById("cardToken");
                        setCardNetwork();
                        cardToken.value = response.id;
                        mercadoPagoForm.submit();
                    }
                });
            }
        });
    </script>
@endpush
