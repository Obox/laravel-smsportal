<?php

namespace Illuminate\Notifications\Channels;

use NeoLikotsi\SMSPortal\RestClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SMSPortalMessage;

class SMSPortalChannel
{
    /**
     * The SMSPortal client instance
     *
     * @var RestClient
     */
    protected $smsPortal;

    /**
     * Create a new SMSPortal channel instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->smsPortal = new RestClient(config('smsportal.client_id'), config('smsportal.secret'), config('smsportal.base_uri'));
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param Notification $notification
     * @return RestClient
     */
    public function send($notifiable, Notification $notification)
    {
        if (config('smsportal.delivery_enabled') != true) {
            return;
        }

        if (!$to = $notifiable->routeNotificationFor('smsportal', $notification)) {
            return;
        }

        $message = $notification->toSmsPortal($notifiable);

        if (is_string($message)) {
            $message = new SMSPortalMessage($message);
        }

        $response = $this->smsPortal->message()->send([
            'messages' => [
                [
                    'destination' => $to,
                    'content' => $message->getContent(),
                ]
            ]
        ]);

        if( isset( $response['eventId'] ) ){
            Cache::tags('smsportal')->put($response['eventId'], [
                'notifiable_model' => get_class($notifiable),
                'notifiable_model_key' => $notifiable->getKey(),
                'notification_model' => get_class($notification),
            ]);
        }

        return $this->smsPortal;
    }
}
