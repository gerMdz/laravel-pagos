<label class="mt-3">Detalles de las tarjetas:</label>
<div class="form-group row">
    <div class="col-sm-5">
        <input type="text" id="cardNumber" data-checkout="cardNumber" class="form-control" placeholder="Nro Tarjeta">
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



@push('scripts')
    <script src="https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js"></script>
    <script>
        const mp = window.Mercadopago;

        mp.setPublishableKey('{{config('services.mercadopago.key')}}');
        mp.getIdentificationTypes();



    </script>

    <script>

    </script>
@endpush
