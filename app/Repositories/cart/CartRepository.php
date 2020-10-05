<?php

namespace App\Repositories\cart;

use App\MercatodoModels\Detail;
use App\MercatodoModels\Order;
use App\MercatodoModels\Product;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartRepository extends BaseRepository
{
    /**
     * @return Order
     */
    public function getModel(): Order
    {
        return new Order();
    }

    /**
     * Function for add producst to cart
     *
     * @param object $data
     */
    public function addToCart(object $data)
    {
        $order = Order::order()->first();

        if ($order) {
            $product = Product::find($data->id);
            $product->quantity = 1;
            $cart[$product->slug] = $product;

            $detailproduct = Detail::where('order_id', $order->id)->where('products_id', $product->id)->first();
            if (!$detailproduct) {
                $detail = new Detail();
                $detail->quantity = 1;
                $detail->products_id = $product->id;
                $detail->order_id = $order->id;
                $detail->save();
                $this->updateTotal($order, $this->total());
            }
        } else {
            $product = Product::find($data->id);
            $product->quantity = 1;
            $cart[$product->slug] = $product;

            $order = new Order();
            $order->code = time();
            $order->total = $this->total();
            $order->status = 0;
            $order->user_id = Auth::user()->id;
            $order->name_receive = Auth::user()->name;
            $order->surname = Auth::user()->surname;
            $order->address = Auth::user()->address;
            $order->phone = Auth::user()->phone;
            $order->save();

            foreach ($cart as $r) {
                $detail = new Detail();
                $detail->quantity = $r->quantity;
                $detail->products_id = $r->id;
                $detail->order_id = $order->id;
                $detail->save();
                $this->updateTotal($order, $this->total());
            }
        }
        Log::channel('contlog')->info("El producto de id: " .
            $detail->products_id . " " . "ha sido agregado al carrito de: " . " " .
            $order->name_receive . " " . $order->surname);
    }

    /**
     * Function for update quantity of products
     *
     * @param string $slug
     * @param int $quantity
     */
    public function updateQuantity(string $slug, int $quantity)
    {
        $product = Product::where('slug', $slug)->first();
        $order = Order::order()->first();
        $detailproduct = Detail::where('order_id', $order->id)
            ->where('products_id', $product->id)->first();

        $detailproduct->quantity = $quantity;
        $detailproduct->save();
        $this->updateTotal($order, $this->total());
    }

    /**
     * Function for calculate the total
     *
     * @return float
     */
    public function total(): float
    {
        $cart = $this->getModel()->with('details.products')->done()->get();

        $total = 0;

        foreach ($cart as $item) {
            foreach ($item->details as $i) {
                $total += $i->products->price * $i->quantity;
            }
        }

        return $total;
    }

    /**
     * Function for update the total
     *
     * @param Order $order
     * @param float $total
     */
    public function updateTotal(Order $order, float $total)
    {
        $order->total = $total;
        $order->save();
    }

    /**
     * Function for delete product of cart
     *
     * @param object $data
     */
    public function deleteProductOfCart(object $data)
    {
        $product = Product::find($data->id);
        $order = Order::order()->first();
        $detailproduct = Detail::where('order_id', $order->id)->where('products_id', $product->id)->first();
        $detailproduct->delete();
    }

    /**
     *Function for empty cart
     */
    public function emptyCart()
    {
        $order = Order::order()->first();
        Detail::where('order_id', $order->id)->delete();
        Order::order()->delete();
    }

    /**
     * function to receive delivery data
     *
     * @param object $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function datesReceiveOrder(object $data): Model
    {
        $order = $this->getModel()->order()->first();
        $order->name_receive = $data->name_receive;
        $order->surname = $data->surname;
        $order->address = $data->address;
        $order->phone = $data->phone;
        $order->save();

        return $order;
    }

    /**
     * Function for see detail of order
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function detail(): Model
    {
        return $this->getModel()->with('details', 'details.products')->done()->first();
    }
}
