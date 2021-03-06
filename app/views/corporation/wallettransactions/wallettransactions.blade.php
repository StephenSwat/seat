@extends('layouts.masterLayout')

@section('html_title', 'Corporation Wallet Transactions')

@section('page_content')

<div class="row">
	<div class="col-md-12">

		<div class="box">
		    <div class="box-header">
		        <h3 class="box-title">Wallet Transactions for: {{ $corporation_name->corporationName }}</h3>
		        <div class="box-tools">
		            <ul class="pagination pagination-sm no-margin pull-right">
						{{ $wallet_transactions->links() }}
		            </ul>
		        </div>
		    </div><!-- /.box-header -->
		    <div class="box-body no-padding">
                <table class="table table-condensed table-hover">
                    <tbody>
                        <tr>
                            {{-- todo here: populate the corporation wallet division too --}}
                            <th>Date</th>
                            <th>#</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Station Name</th>
                        </tr>
                        @foreach ($wallet_transactions as $e)
                            <tr @if ($e->transactionType == 'buy')class="danger" @endif>
                                <td>
                                	<spanp data-toggle="tooltip" title="" data-original-title="{{ $e->transactionDateTime }}">
                                		{{ Carbon\Carbon::parse($e->transactionDateTime)->diffForHumans() }}
                                	</span>
                                </td>
                                <td>{{ $e->quantity }}</td>
                                <td>
                                    <img src='http://image.eveonline.com/Type/{{ $e->typeID }}_32.png' style='width: 18px;height: 18px;'>
                                    {{ $e->typeName }}
                                </td>
                                <td>{{ number_format($e->price, 2, '.', ' ') }} ISK</td>
                                <td>{{ $e->clientName }}</td>
                                <td>{{ $e->transactionType }}</td>
                                <td>{{ $e->stationName }}</td>
                            </tr>
                        @endforeach

                	</tbody>
               	</table>
		    </div><!-- /.box-body -->
		    <div class="pull-right">{{ $wallet_transactions->links() }}</div>
		</div>
	</div>
</div>
	
@stop
