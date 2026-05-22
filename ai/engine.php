<?php
/**
 * AI Engine — Smart Parking
 *
 * FIX: dashboard.php referenced /ai/Moteur_IA.php and /ai/predict.php
 *      but those files did NOT EXIST in the project.
 *      This file consolidates all AI logic in one place.
 *
 * FEATURES:
 *  - recommendPlace()  : rule-based recommendation (priority + distance)
 *  - predictOccupancy(): time-series-inspired prediction from dataset
 *  - parkingStatus()   : occupancy rate with colored label
 *  - getAIPrediction() : returns structured AI data for the dashboard
 */

/**
 * Recommend the best available parking spot.
 * Logic: lowest priority number = closest to entrance.
 */
function recommendPlace(PDO $pdo): ?array
{
    $stmt = $pdo->prepare("
        SELECT * FROM parking_places
        WHERE status = 'libre'
        ORDER BY priorite ASC
        LIMIT 1
    ");
    $stmt->execute();
    return $stmt->fetch() ?: null;
}

/**
 * Predict parking occupancy for the next hour.
 * Uses a simple lookup table built from the CSV dataset.
 * 
 * IMPROVEMENT: In production, replace with a trained sklearn model
 * called via Python subprocess or a REST microservice.
 */
function predictOccupancy(PDO $pdo): array
{
    $hour      = (int) date('G');        // 0–23
    $dayOfWeek = (int) date('N');        // 1=Mon … 7=Sun
    $isWeekend = $dayOfWeek >= 6 ? 1 : 0;

    // Lookup table derived from dataset.csv
    // Keys: [is_weekend][hour_bucket] => predicted occupancy %
    $lookup = [
        0 => [ // weekday
            [0,7]   => 10,
            [8,9]   => 30,
            [10,11] => 65,
            [12,13] => 88,
            [14,15] => 50,
            [16,17] => 45,
            [18,23] => 25,
        ],
        1 => [ // weekend
            [0,9]   => 15,
            [10,11] => 40,
            [12,14] => 60,
            [15,16] => 70,
            [17,19] => 88,
            [20,23] => 92,
        ],
    ];

    $predicted = 50; // default
    foreach ($lookup[$isWeekend] as $range => $occ) {
        if ($hour >= $range[0] && $hour <= $range[1]) {
            $predicted = $occ;
            break;
        }
    }

    // Slight jitter based on real current data
    $current = $pdo->query("SELECT COUNT(*) FROM parking_places WHERE status='occupied'")->fetchColumn();
    $total   = $pdo->query("SELECT COUNT(*) FROM parking_places")->fetchColumn();
    if ($total > 0) {
        $realRate  = round(($current / $total) * 100);
        $predicted = (int) round(($predicted * 0.6) + ($realRate * 0.4));
    }

    $label = $predicted >= 80 ? 'Saturé' : ($predicted >= 50 ? 'Moyen' : 'Faible affluence');
    $color = $predicted >= 80 ? 'danger' : ($predicted >= 50 ? 'warning' : 'success');
    $emoji = $predicted >= 80 ? '🔴' : ($predicted >= 50 ? '🟠' : '🟢');

    return [
        'predicted_occupancy' => $predicted,
        'label'               => $label,
        'color'               => $color,
        'emoji'               => $emoji,
        'hour'                => $hour,
        'is_weekend'          => $isWeekend,
    ];
}

/**
 * Return a human-readable parking status.
 */
function parkingStatus(PDO $pdo): string
{
    $total = (int) $pdo->query("SELECT COUNT(*) FROM parking_places")->fetchColumn();
    if ($total === 0) return '⚪ No places configured';

    $occupied = (int) $pdo->query("SELECT COUNT(*) FROM parking_places WHERE status='occupied'")->fetchColumn();
    $rate = ($occupied / $total) * 100;

    if ($rate >= 80) return '🔴 Saturé ('  . $occupied . '/' . $total . ')';
    if ($rate >= 50) return '🟠 Moyen ('   . $occupied . '/' . $total . ')';
    return                  '🟢 Disponible (' . $occupied . '/' . $total . ')';
}

/**
 * Full AI payload for the dashboard API.
 */
function getAIPayload(PDO $pdo): array
{
    $recommended = recommendPlace($pdo);
    $prediction  = predictOccupancy($pdo);
    $status      = parkingStatus($pdo);

    return [
        'recommended_place' => $recommended,
        'prediction'        => $prediction,
        'status'            => $status,
    ];
}
