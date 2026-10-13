<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo json_encode(['status' => 'disabled', 'message' => 'Rating feature is currently disabled.']);
exit();

try {
    if ($action === 'rate_order_all') {
        $order_id = !empty($input['order_id']) ? intval($input['order_id']) : null;
        $restaurant_id = !empty($input['restaurant_id']) ? intval($input['restaurant_id']) : null;
        $restaurant_rating = !empty($input['restaurant_rating']) ? intval($input['restaurant_rating']) : 0;
        $restaurant_review = trim($input['restaurant_review'] ?? '');
        $food_ratings = $input['food_ratings'] ?? [];

        if ($restaurant_id > 0 && $restaurant_rating >= 1 && $restaurant_rating <= 5) {
            // Check if existing restaurant review
            if ($order_id) {
                $check = $conn->prepare("SELECT id FROM restaurant_reviews WHERE user_id = ? AND restaurant_id = ? AND order_id = ?");
                $check->execute([$user_id, $restaurant_id, $order_id]);
            } else {
                $check = $conn->prepare("SELECT id FROM restaurant_reviews WHERE user_id = ? AND restaurant_id = ? AND order_id IS NULL");
                $check->execute([$user_id, $restaurant_id]);
            }
            $existing = $check->fetch();

            if ($existing) {
                $upd = $conn->prepare("UPDATE restaurant_reviews SET rating = ?, review = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?");
                $upd->execute([$restaurant_rating, $restaurant_review, $existing['id']]);
            } else {
                $ins = $conn->prepare("INSERT INTO restaurant_reviews (restaurant_id, user_id, order_id, rating, review) VALUES (?, ?, ?, ?, ?)");
                $ins->execute([$restaurant_id, $user_id, $order_id, $restaurant_rating, $restaurant_review]);
            }
        }

        // Process food items
        if (is_array($food_ratings)) {
            foreach ($food_ratings as $f) {
                $f_id = intval($f['food_id'] ?? 0);
                $f_rating = intval($f['rating'] ?? 0);
                $f_review = trim($f['review'] ?? '');

                if ($f_id > 0 && $f_rating >= 1 && $f_rating <= 5) {
                    if ($order_id) {
                        $f_check = $conn->prepare("SELECT id FROM food_reviews WHERE user_id = ? AND food_id = ? AND order_id = ?");
                        $f_check->execute([$user_id, $f_id, $order_id]);
                    } else {
                        $f_check = $conn->prepare("SELECT id FROM food_reviews WHERE user_id = ? AND food_id = ? AND order_id IS NULL");
                        $f_check->execute([$user_id, $f_id]);
                    }
                    $f_existing = $f_check->fetch();

                    if ($f_existing) {
                        $f_upd = $conn->prepare("UPDATE food_reviews SET rating = ?, review = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $f_upd->execute([$f_rating, $f_review, $f_existing['id']]);
                    } else {
                        $f_ins = $conn->prepare("INSERT INTO food_reviews (food_id, user_id, order_id, rating, review) VALUES (?, ?, ?, ?, ?)");
                        $f_ins->execute([$f_id, $user_id, $order_id, $f_rating, $f_review]);
                    }
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Thank you! Your ratings and feedback have been submitted successfully.'
        ]);
        exit();

    } elseif ($action === 'rate_food_single') {
        $food_id = intval($input['food_id'] ?? 0);
        $rating = intval($input['rating'] ?? 0);
        $review = trim($input['review'] ?? '');

        if ($food_id <= 0 || $rating < 1 || $rating > 5) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid rating or food item.']);
            exit();
        }

        $check = $conn->prepare("SELECT id FROM food_reviews WHERE user_id = ? AND food_id = ? LIMIT 1");
        $check->execute([$user_id, $food_id]);
        $existing = $check->fetch();

        if ($existing) {
            $upd = $conn->prepare("UPDATE food_reviews SET rating = ?, review = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?");
            $upd->execute([$rating, $review, $existing['id']]);
        } else {
            $ins = $conn->prepare("INSERT INTO food_reviews (food_id, user_id, rating, review) VALUES (?, ?, ?, ?)");
            $ins->execute([$food_id, $user_id, $rating, $review]);
        }

        $avg_stmt = $conn->prepare("SELECT ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as total_reviews FROM food_reviews WHERE food_id = ?");
        $avg_stmt->execute([$food_id]);
        $stats = $avg_stmt->fetch();

        echo json_encode([
            'status' => 'success',
            'message' => 'Rating submitted successfully!',
            'avg_rating' => $stats['avg_rating'] ?? $rating,
            'total_reviews' => $stats['total_reviews'] ?? 1
        ]);
        exit();

    } elseif ($action === 'rate_restaurant_single') {
        $restaurant_id = intval($input['restaurant_id'] ?? 0);
        $rating = intval($input['rating'] ?? 0);
        $review = trim($input['review'] ?? '');

        if ($restaurant_id <= 0 || $rating < 1 || $rating > 5) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid rating or restaurant.']);
            exit();
        }

        $check = $conn->prepare("SELECT id FROM restaurant_reviews WHERE user_id = ? AND restaurant_id = ? LIMIT 1");
        $check->execute([$user_id, $restaurant_id]);
        $existing = $check->fetch();

        if ($existing) {
            $upd = $conn->prepare("UPDATE restaurant_reviews SET rating = ?, review = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?");
            $upd->execute([$rating, $review, $existing['id']]);
        } else {
            $ins = $conn->prepare("INSERT INTO restaurant_reviews (restaurant_id, user_id, rating, review) VALUES (?, ?, ?, ?)");
            $ins->execute([$restaurant_id, $user_id, $rating, $review]);
        }

        $avg_stmt = $conn->prepare("SELECT ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as total_reviews FROM restaurant_reviews WHERE restaurant_id = ?");
        $avg_stmt->execute([$restaurant_id]);
        $stats = $avg_stmt->fetch();

        echo json_encode([
            'status' => 'success',
            'message' => 'Restaurant rating submitted successfully!',
            'avg_rating' => $stats['avg_rating'] ?? $rating,
            'total_reviews' => $stats['total_reviews'] ?? 1
        ]);
        exit();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        exit();
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}
