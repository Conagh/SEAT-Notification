<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015 to present Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Seat\Notifications\Notifications\Characters\Discord;

use Seat\Eveapi\Models\Assets\AssetSafetyNotification; // adjust this to your actual model namespace
use Seat\Notifications\Notifications\AbstractDiscordNotification;
use Seat\Notifications\Services\Discord\Messages\DiscordEmbed;
use Seat\Notifications\Services\Discord\Messages\DiscordEmbedField;
use Seat\Notifications\Services\Discord\Messages\DiscordMessage;
use Seat\Notifications\Traits\NotificationTools;

/**
 * Class AssetSafety.
 *
 * @package Seat\Notifications\Notifications\Characters\Discord
 */
class AssetSafety extends AbstractDiscordNotification
{
    use NotificationTools;

    /**
     * @var \Seat\Eveapi\Models\Assets\AssetSafetyNotification
     */
    private $notification;

    /**
     * AssetSafety constructor.
     *
     * @param  \Seat\Eveapi\Models\Assets\AssetSafetyNotification  $notification
     */
    public function __construct(AssetSafetyNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * @param  DiscordMessage  $message
     * @param  $notifiable
     */
    public function populateMessage(DiscordMessage $message, $notifiable)
    {
        $message
            ->embed(function (DiscordEmbed $embed) {
                // Use the notification time instead of killmail time
                $embed->timestamp($this->notification->notification_time);
                $embed->author('SeAT Asset Safety Notification', asset('web/img/favico/apple-icon-180x180.png'));

                // Title uses asset name and location
                $embed->title(sprintf(
                    'Asset Safety Alert: %s at %s',
                    $this->notification->asset->name,
                    $this->notification->asset->location
                ));

                // Link to the asset safety notification endpoint
                $embed->field(function (DiscordEmbedField $field) {
                    $field
                        ->name('Notification')
                        ->value('https://esi.evetech.net/latest/notification/asset-safety/' . $this->notification->notification_id . '/')
                        ->long();
                });

                // Asset details
                $embed->field(function (DiscordEmbedField $field) {
                    $field
                        ->name('Asset')
                        ->value(sprintf("Name: %s\nType: %s",
                            $this->notification->asset->name,
                            $this->notification->asset->type // adjust if necessary
                        ))
                        ->long();
                });

                // Safety status
                $embed->field(function (DiscordEmbedField $field) {
                    $field
                        ->name('Status')
                        ->value($this->notification->status)
                        ->long();
                });

                // Additional details
                $embed->field(function (DiscordEmbedField $field) {
                    $field
                        ->name('Details')
                        ->value(sprintf("Reported at: %s\nEstimated Value: %s ISK",
                            carbon($this->notification->notification_time)->toTimeString(),
                            number_format($this->notification->asset->value)
                        ))
                        ->long();
                });

                // Thumbnail (for example, asset type icon)
                $embed->thumb($this->typeIconUrl($this->notification->asset->type_id));

                // Footer and color based on safety status
                $embed->footer('Asset Safety', asset('web/img/safety.png'));

                if ($this->notification->status === 'Unsafe') {
                    $embed->color(DiscordMessage::ERROR);
                } else {
                    $embed->color(DiscordMessage::SUCCESS);
                }
            });
    }
}
