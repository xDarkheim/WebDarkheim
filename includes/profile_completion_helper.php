<?php

/**
 * Profile Completion Utility
 * Unified function for calculating profile completion across all pages
 */

if (!function_exists('calculateProfileCompletion')) {
    /**
     * Calculate profile completion percentage
     *
     * @param array $user User data from database
     * @param array|null $profile Client profile data from database
     * @return array Completion statistics
     */
    function calculateProfileCompletion($user, $profile): array {
        $fields = [
            'email' => !empty($user['email']),
            'company' => !empty($profile['company_name'] ?? ''),
            'position' => !empty($profile['position'] ?? ''),
            'bio' => !empty($profile['bio'] ?? ''),
            'location' => !empty($profile['location'] ?? ''),
            'website' => !empty($profile['website'] ?? ''),
            'skills' => !empty($profile['skills'] ?? '') && $profile['skills'] !== '[]',
        ];

        $completed = count(array_filter($fields));
        $total = count($fields);
        $percentage = round(($completed / $total) * 100);

        return [
            'percentage' => $percentage,
            'completed' => $completed,
            'total' => $total,
            'fields' => $fields,
            'missing' => array_keys(array_filter($fields, fn($v) => !$v))
        ];
    }
}

if (!function_exists('getClientProfileData')) {
    /**
     * Get client profile data for a user
     *
     * @param object $database Database handler
     * @param int $userId User ID
     * @return array|null Client profile data or null
     */
    function getClientProfileData($database, int $userId): ?array {
        try {
            $sql = "SELECT * FROM client_profiles WHERE user_id = ?";
            $stmt = $database->getConnection()->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            error_log("Error fetching client profile: " . $e->getMessage());
            return null;
        }
    }
}
