<?php

/**
 * Social Media Helper
 * Manages links and integration with social networks
 * Provides methods for displaying social links, icons, and colors
 * Uses ConfigurationManager for dynamic settings loading
 * Uses LoggerInterface for logging
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Helpers;


class SocialMediaHelper
{
    private static ?array $socialNetworks = null;

    /**
     * Get all active social networks from settings
     */
    public static function getAllSocialNetworks(): array
    {
        if (self::$socialNetworks === null) {
            self::$socialNetworks = [];

            // Get settings from the unified configuration system
            // Use correct setting keys as stored in the DB
            $networks = [
                'facebook' => getSetting('social', 'social_facebook', ''),
                'twitter' => getSetting('social', 'social_twitter', ''),
                'linkedin' => getSetting('social', 'social_linkedin', ''),
                'github' => getSetting('social', 'social_github', ''),
                'instagram' => getSetting('social', 'social_instagram', ''),
                'youtube' => getSetting('social', 'social_youtube', ''),
                'discord' => getSetting('social', 'social_discord', ''),
                'telegram' => getSetting('social', 'social_telegram', ''),
                'behance' => getSetting('social', 'social_behance', ''),
                'dribbble' => getSetting('social', 'social_dribbble', ''),
                'medium' => getSetting('social', 'social_medium', ''),
                'dev_to' => getSetting('social', 'social_dev_to', ''),
                'whatsapp' => getSetting('social', 'social_whatsapp', ''),
                'skype' => getSetting('social', 'social_skype', ''),
                'slack' => getSetting('social', 'social_slack', ''),
                'tiktok' => getSetting('social', 'social_tiktok', ''),
                'reddit' => getSetting('social', 'social_reddit', ''),
                'pinterest' => getSetting('social', 'social_pinterest', ''),
            ];

            foreach ($networks as $network => $url) {
                if (!empty($url)) {
                    self::$socialNetworks[$network] = [
                        'name' => self::getNetworkDisplayName($network),
                        'url' => $url,
                        'icon' => self::getSocialIcon($network),
                        'color' => self::getNetworkColor($network)
                    ];
                }
            }
        }

        return self::$socialNetworks;
    }

    /**
     * Get display name of the social network
     */
    private static function getNetworkDisplayName(string $network): string
    {
        $names = [
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'github' => 'GitHub',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
            'discord' => 'Discord',
            'telegram' => 'Telegram',
            'behance' => 'Behance',
            'dribbble' => 'Dribbble',
            'medium' => 'Medium',
            'dev_to' => 'Dev.to',
            'whatsapp' => 'WhatsApp',
            'skype' => 'Skype',
            'slack' => 'Slack',
            'tiktok' => 'TikTok',
            'reddit' => 'Reddit',
            'pinterest' => 'Pinterest',
        ];

        return $names[$network] ?? ucfirst($network);
    }

    /**
     * Get brand color of the social network
     */
    private static function getNetworkColor(string $network): string
    {
        $colors = [
            'facebook' => '#1877F2',
            'twitter' => '#1DA1F2',
            'linkedin' => '#0A66C2',
            'github' => '#181717',
            'instagram' => '#E4405F',
            'youtube' => '#FF0000',
            'discord' => '#5865F2',
            'telegram' => '#0088CC',
            'behance' => '#053eff',
            'dribbble' => '#e74c3c',
            'medium' => '#00ab6c',
            'dev_to' => '#0e0e0e',
            'whatsapp' => '#25D366',
            'skype' => '#00aff0',
            'slack' => '#4A154B',
            'tiktok' => '#69C9D0',
            'reddit' => '#FF4500',
            'pinterest' => '#E60023',
        ];

        return $colors[$network] ?? '#666666';
    }

    /**
     * Get FontAwesome icon for the social network
     */
    public static function getSocialIcon(string $network): string
    {
        $icons = [
            'facebook' => 'fab fa-facebook-f',
            'twitter' => 'fab fa-twitter',
            'linkedin' => 'fab fa-linkedin-in',
            'github' => 'fab fa-github',
            'instagram' => 'fab fa-instagram',
            'youtube' => 'fab fa-youtube',
            'discord' => 'fab fa-discord',
            'telegram' => 'fab fa-telegram-plane',
            'behance' => 'fab fa-behance',
            'dribbble' => 'fab fa-dribbble',
            'medium' => 'fab fa-medium-m',
            'dev_to' => 'fab fa-dev',
            'whatsapp' => 'fab fa-whatsapp',
            'skype' => 'fab fa-skype',
            'slack' => 'fab fa-slack',
            'tiktok' => 'fab fa-tiktok',
            'reddit' => 'fab fa-reddit',
            'pinterest' => 'fab fa-pinterest-p',
        ];

        return $icons[$network] ?? 'fas fa-link';
    }

    /**
     * Get HTML for displaying social networks
     */
    public static function renderSocialLinks(string $cssClass = 'social-links'): string
    {
        $networks = self::getAllSocialNetworks();

        if (empty($networks)) {
            return '';
        }

        $html = '<div class="' . htmlspecialchars($cssClass) . '">';

        foreach ($networks as $key => $network) {
            $html .= sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer" class="social-link social-link-%s" title="%s" style="color: %s;"><i class="%s"></i></a>',
                htmlspecialchars($network['url']),
                htmlspecialchars($key),
                htmlspecialchars($network['name']),
                htmlspecialchars($network['color']),
                htmlspecialchars($network['icon'])
            );
        }

        $html .= '</div>';

        return $html;
    }

}
