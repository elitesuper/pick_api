<?php

namespace Marvel\Payments;

use Exception;
use Illuminate\Support\Facades\Http;
use Marvel\Exceptions\MarvelException;
use Marvel\Payments\PaymentInterface;
use Marvel\Payments\Base;
use Unicodeveloper\Paystack\Facades\Paystack as PaystackFacade;


class Paystack extends Base implements PaymentInterface
{
  public function getIntent($data)
  {
    try {
      extract($data);
      return ['redirect_url' => PaystackFacade::getAuthorizationUrl()->url,  'is_redirect' => true];
    } catch (Exception $e) {
      throw new MarvelException(SOMETHING_WENT_WRONG_WITH_PAYMENT);
    }
  }

  public function verify($paymentId)
  {
    try {
      $response = Http::withHeaders([
        "Authorization" => "Bearer " . config('shop.paystack.secret_key'),
        "Cache-Control" => "no-cache",
      ])->get(config('shop.paystact.payment_url') . '/verify' . '/' . $paymentId);

      return $response->successful();
    } catch (Exception $e) {
      throw new MarvelException(SOMETHING_WENT_WRONG_WITH_PAYMENT);
    }
  }
}
