Заказ №{!! $order->number !!} оплачен.

Состав заказа:
@foreach ($order->items as $item)
- {!! $item->name !!} ({!! $item->sku !!}): {!! $item->quantity !!} × {!! $item->price !!} {!! $order->currency !!}
@endforeach

Доставка: {!! $order->delivery_price !!} {!! $order->currency !!}
Скидка: {!! $order->discount !!} {!! $order->currency !!}
Итого: {!! $order->total !!} {!! $order->currency !!}
