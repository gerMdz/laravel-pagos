<label class="mt-3">Detalles de las tarjetas:</label>
<div class="form-group row">
    <div class="col-4">
        <input class="form-control" type="text" name="card" placeholder="Nro tarjeta">
    </div>
    <div class="col-sm-2">
        <input type="text" name="cvc" class="form-control" placeholder="CVC">
    </div>

    <div class="col-sm-2">
        <input type="text" name="mounth" class="form-control" placeholder="MM">
    </div>
    <div class="col-sm-2">
        <input type="text" name="year" class="form-control" placeholder="AA">
    </div>
    <div class="col-sm-2">
        <select id="" class="form-select" name="network">
            <option selected>Seleccione ...</option>
            <option value="visa">Visa</option>
            <option value="amex">AMEX</option>
            <option value="diners">Diners</option>
            <option value="mastercard">Mastercard</option>
        </select>
    </div>
</div>
<div class="form-group row">
    <div class="col-5">
        <input type="text" name="name" class="form-control" placeholder="Nombre">
    </div>
    <div class="col-5">
        <input type="email" class="form-control" name="email"  placeholder="email@ejemplo.com">
    </div>
</div>


<div class="form-group row">
    <div class="col">
        <small class="form-text text-mute" role="alert">
            Su pago será convertido a {{ strtoupper(config('services.payu.base_currency')) }}
        </small>
    </div>
</div>
