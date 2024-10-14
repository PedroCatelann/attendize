<section id='order_form' class="container">
    <div class="row">
        <h1 class="section_head">
            @lang("Public_ViewEvent.payment_information")
        </h1>
        <h1>
            {{$customer_id}}
        </h1>
    </div>
    
    @if($payment_failed)
    <div class="row">
        <div class="col-md-8 alert-danger" style="text-align: left; padding: 10px">
            @lang("Order.payment_failed")
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-md-12" style="text-align: center">
            @lang("Public_ViewEvent.below_order_details_header")
        </div>
        
        <div class="col-md-4 col-md-push-8">
            <div class="panel">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="ico-cart mr5"></i>
                        @lang("Public_ViewEvent.order_summary")
                    </h3>
                </div>
                <div class="panel-body pt0">
                    <table class="table mb0 table-condensed">
                        @foreach($tickets as $ticket)
                        <tr>
                            <td class="pl0">{{{$ticket['ticket']['title']}}} X <b>{{$ticket['qty']}}</b></td>
                            <td style="text-align: right;">
                                @isFree($ticket['full_price'])
                                @lang("Public_ViewEvent.free")
                                @else
                                {{ money($ticket['full_price'], $event->currency) }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
                @if($order_total > 0)
                <div class="panel-footer">
                    <h5>
                        @lang("Public_ViewEvent.total"): <span style="float: right;"><b>{{ $orderService->getOrderTotalWithBookingFee(true) }}</b></span>
                    </h5>
                    @if($event->organiser->charge_tax)
                    <h5>
                        {{ $event->organiser->tax_name }} ({{ $event->organiser->tax_value }}%):
                        <span style="float: right;"><b>{{ $orderService->getTaxAmount(true) }}</b></span>
                    </h5>
                    <h5>
                        <strong>@lang("Public_ViewEvent.grand_total")</strong>
                        <span style="float: right;"><b>{{ $orderService->getGrandTotal(true) }}</b></span>
                    </h5>
                    @endif
                </div>
                @endif
            </div>
            <div class="help-block">
                {!! @trans("Public_ViewEvent.time", ["time"=>"<span id='countdown'></span>"]) !!}
            </div>
        </div>

        <div class="col-md-8 col-md-pull-4">
            <div class="row">
            {!! Form::open([ 'method' => 'POST', 'id' => 'paymentForm']) !!} 
                {!! Form::hidden('customerId', $customer_id) !!}
                {!! Form::hidden('order_total', $order_total) !!}
                {!! Form::hidden('event_id', $event->id) !!}

                <h3> @lang("Public_ViewEvent.your_information")</h3>

                <div class="row">
                    <div class="col-xs-12">
                        <div class="form-group">
                            {!! Form::label('billingType', 'Opção de Pagamento') !!}
                            {!! Form::select('billingType', [
                                'boleto' => 'BOLETO',
                                'pix' => 'PIX',
                                'cartao_credito' => 'CARTÃO DE CRÉDITO'
                            ], null, ['required' => 'required', 'class' => 'form-control', 'id' => 'billingType']) !!}
                        </div>
                    </div>                    
                </div>

                <div class="row" id="qntd_parcelas" style="display: none;">
                    <div class="col-xs-12">
                        <div class="form-group">
                        {!! Form::label('parcelas', 'Quantidade de Parcelas') !!}
                        {!! Form::select('parcelas', 
                            array_combine(range(1, $vezes_no_cartao), range(1, $vezes_no_cartao)), // Gerando um array com valores de 1 a 5
                            null, 
                            ['class' => 'form-control', 'id' => 'parcelas']) !!}

                        </div>
                    </div>                    
                </div>
                    
                {!! Form::submit(trans("Public_ViewEvent.checkout_order"), ['class' => 'btn btn-lg btn-success card-submit', 'style' => 'width:100%;']) !!}
            {!! Form::close() !!}
            </div>
        </div>
    </div>
    <img src="https://cdn.attendize.com/lg.png" />
</section>

@if(session()->get('message'))
<script>showMessage('{{session()->get('message')}}');</script>
@endif
<script>


document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('paymentForm');
    var billingTypeSelect = document.getElementById('billingType');
    var creditCardDiv = document.getElementById('qntd_parcelas'); // Corrigido o ID

    billingTypeSelect.addEventListener('change', function() {
        if (this.value === 'cartao_credito') {
            creditCardDiv.style.display = 'block'; // Exibe a div
        } else {
            creditCardDiv.style.display = 'none';  // Oculta a div
        }
    });

    paymentForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Impede o envio padrão do formulário

        // Coleta os dados do formulário
        const formData = new FormData(paymentForm);
        
        // Converte FormData para um objeto
        const formObject = Object.fromEntries(formData.entries());
        
        fetch('{{ route('createCobrancaAsaas') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json', // Defina o tipo de conteúdo como JSON
            },
            body: JSON.stringify(formObject), // Converte o objeto para JSON
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição');
            }
            return response.json(); // Converte a resposta em JSON
        })
        .then(data => {
            if(data.invoiceUrl && data.tipoPagamento === 'pix') {
                window.open(data.invoiceUrl, '_blank')
            }
            if(data.invoiceUrl && data.tipoPagamento === 'cartao_credito') {
                window.open(data.invoiceUrl, '_blank')
            }
            if (data.bankSlipUrl && data.tipoPagamento === 'boleto') {
                // Abre a URL do boleto em uma nova aba
                window.open(data.bankSlipUrl, '_blank');
            } 
            /* else {
                alert('Erro ao gerar o pagamento: ' + (data.error || 'Desconhecido'));
            } */
        })
        .catch(error => {
            console.log('Erro:', error);
            alert('Erro ao criar a cobrança.');
        });
    });
});
</script>
