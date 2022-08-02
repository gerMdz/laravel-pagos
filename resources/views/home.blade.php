@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Dashboard') }}</div>

                    <div class="card-body">
                        <form action="#" method="POST" id="paymentForm">
                            @csrf
                            <div class="row">
                                <div class="col-auto">
                                    <label for="payMont"> ¿Cuanto desea pagar?</label>
                                    <input
                                        id="payMont"
                                        type="number"
                                        min="5"
                                        step="0.01"
                                        class="form-control"
                                        value="{{ mt_rand(500, 10000) /100 }}"
                                        required
                                    >
                                    <small class="form-text text-muted">
                                        Use valores decimales separados por puntos
                                    </small>

                                </div>
                                <div class="col-auto">
                                    <label for="payCurrency">Moneda</label>
                                    <select name="currency" required id="payCurrency" class="form-select">
                                        @foreach($currencies  as $currency)
                                            <option value="{{$currency->iso}}">
                                                {{strtoupper($currency->iso)}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <label>Seleccione plataforma</label>
                                    <div class="form-group" id="toggler">
                                        <div class="btn-group btn-group-toggle"
                                             data-bs-toggle="buttons"
                                        >
                                            @foreach($paymentPlatforms as $paymentPlatform)
                                                <label
                                                       class="btn btn-outline-secondary rounded m-2 p-1"
                                                       data-bs-target="#{{$paymentPlatform->name}}Collapse"
                                                       data-bs-toggle="collapse"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="payment_platform"
                                                        value="{{$paymentPlatform->id}}"
                                                        required

                                                    >
                                                    <img class="img-thumbnail" src="{{asset($paymentPlatform->image)}}"
                                                         alt="{{$paymentPlatform->name}}">

                                                </label>

                                            @endforeach
                                        </div>
                                        @foreach($paymentPlatforms as $paymentPlatform)
                                            <div id="{{$paymentPlatform->name}}Collapse" class="collapse" data-bs-parent="#toggler">
                                                @includeIf('components.'.strtolower($paymentPlatform->name).'-collapse')
                                            </div>

                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="row  mt-3">
                                <div class="col text-center">
                                    <button type="submit" id="payButton"
                                            class="btn btn-primary btn-lg"
                                    >
                                        Pagar
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
