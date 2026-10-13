<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');

if (mb_strlen($query) < 2) {
    echo json_encode(['results' => [], 'count' => 0]);
    exit();
}

// Strict Synonym / Typo Dictionary for Exact & Typo-Tolerant Matching
$synonyms = [
    // Paneer / Cottage Cheese & typos
    'panner' => ['paneer', 'cottage cheese'],
    'paner' => ['paneer', 'cottage cheese'],
    'paneer' => ['paneer', 'cottage cheese'],
    'cottage cheese' => ['paneer', 'cottage cheese'],
    
    // Cheese & typos
    'chese' => ['cheese'],
    'cheese' => ['cheese'],
    
    // Mushroom & typos
    'mashroom' => ['mushroom'],
    'musroom' => ['mushroom'],
    'mushroom' => ['mushroom', 'mushrooms'],
    'mushrooms' => ['mushroom', 'mushrooms'],
    
    // Tacos & Mexican
    'taco' => ['taco', 'tacos'],
    'tacos' => ['taco', 'tacos'],
    'takos' => ['taco', 'tacos'],
    'tako' => ['taco', 'tacos'],
    'quesadilla' => ['quesadilla', 'quesadillas'],
    'quesadillas' => ['quesadilla', 'quesadillas'],
    
    // Khichdi
    'khichdi' => ['khichdi', 'khichri'],
    'khichri' => ['khichdi', 'khichri'],
    'khichadi' => ['khichdi'],
    
    // Dimsums / Dumplings / Baos
    'dimsum' => ['dimsum', 'dimsums', 'dim sum'],
    'dimsums' => ['dimsum', 'dimsums', 'dim sum'],
    'dim sum' => ['dimsum', 'dimsums', 'dim sum'],
    'dumpling' => ['dumpling', 'dumplings'],
    'dumplings' => ['dumpling', 'dumplings'],
    'bao' => ['bao', 'baos'],
    'baos' => ['bao', 'baos'],
    
    // Coffee & Drinks
    'coffe' => ['coffee'],
    'coffee' => ['coffee'],
    'latte' => ['latte'],
    'espresso' => ['espresso'],
    'cappuccino' => ['cappuccino'],
    'matcha' => ['matcha'],
    
    // Salad
    'salad' => ['salad', 'salads'],
    'salads' => ['salad', 'salads'],
    
    // Manchurian
    'manchurian' => ['manchurian', 'mancurian'],
    'mancurian' => ['manchurian'],
    
    // Chilli / Starters
    'chilli' => ['chilli', 'chili', 'chilly'],
    'chili' => ['chilli', 'chili', 'chilly'],
    'chilly' => ['chilli', 'chili', 'chilly'],
    
    // Pasta & Italian
    'pasta' => ['pasta'],
    'ravioli' => ['ravioli'],
    'spaghetti' => ['spaghetti'],
    'gnocchi' => ['gnocchi'],
    'lasagna' => ['lasagna', 'lasagne'],
    
    // Sushi
    'sushi' => ['sushi', 'maki'],
    
    // Burgers & Sandwiches
    'burger' => ['burger', 'burgers'],
    'burgers' => ['burger', 'burgers'],
    'sandwich' => ['sandwich', 'sandwiches'],
    'sandwiches' => ['sandwich', 'sandwiches'],
    'toastie' => ['toastie', 'toasties'],
    
    // Desserts
    'cake' => ['cake', 'cheesecake'],
    'cheesecake' => ['cheesecake', 'cheese cake'],
    'brownie' => ['brownie', 'brownies'],
    'tiramisu' => ['tiramisu'],
    'fondue' => ['fondue'],
    'dessert' => ['dessert', 'desserts', 'cake', 'cheesecake', 'brownie', 'tiramisu'],
    'desserts' => ['dessert', 'desserts', 'cake', 'cheesecake', 'brownie', 'tiramisu'],
    
    // Pizza & typos
    'piza' => ['pizza', 'pizzas'],
    'pizaa' => ['pizza', 'pizzas'],
    'pizza' => ['pizza', 'pizzas'],
    'pizzas' => ['pizza', 'pizzas'],
    
    // Soups
    'soup' => ['soup', 'soups'],
    'soups' => ['soup', 'soups'],
    
    // Combos & Meals
    'combo' => ['combo', 'combos', 'meal'],
    'combos' => ['combo', 'combos', 'meal'],
    'meal' => ['meal', 'combo'],

    // Sides
    'fries' => ['fries'],
    'wedges' => ['wedges', 'wedge'],
    'nachos' => ['nachos', 'nacho'],
    'garlic bread' => ['garlic bread', 'garlic / jain bread', 'bread'],
    'bread' => ['garlic bread', 'bread']
];

$search_terms = [$query];
$q_lower = strtolower($query);

foreach ($synonyms as $key => $alt_list) {
    if (str_contains($q_lower, $key)) {
        foreach ($alt_list as $alt) {
            if (!in_array($alt, $search_terms)) {
                $search_terms[] = $alt;
            }
        }
    }
}

try {
    // 1. Search Matching Restaurants
    $rest_sql = "
        SELECT r.id, r.restaurant_name, r.logo, r.city, r.address,
               (SELECT COUNT(*) FROM food_items f WHERE f.restaurant_id = r.id AND f.is_available = 1) AS total_dishes
        FROM restaurants r
        WHERE r.status = 'Approved' AND (r.restaurant_name LIKE ? OR r.description LIKE ? OR r.city LIKE ?)
        ORDER BY 
            CASE 
                WHEN r.restaurant_name LIKE ? THEN 1
                WHEN r.restaurant_name LIKE ? THEN 2
                ELSE 3
            END,
            r.restaurant_name ASC
        LIMIT 4
    ";
    $exact_param = $query . '%';
    $like_param = '%' . $query . '%';
    $rest_stmt = $conn->prepare($rest_sql);
    $rest_stmt->execute([$like_param, $like_param, $like_param, $exact_param, $like_param]);
    $restaurants = $rest_stmt->fetchAll();

    // 2. Search Matching Food Dishes
    $where_clauses = [];
    $params = [];

    foreach ($search_terms as $term) {
        $where_clauses[] = "(f.name LIKE ? OR f.description LIKE ? OR c.name LIKE ? OR r.restaurant_name LIKE ?)";
        $t_param = '%' . $term . '%';
        $params[] = $t_param;
        $params[] = $t_param;
        $params[] = $t_param;
        $params[] = $t_param;
    }

    $where_sql = implode(' OR ', $where_clauses);

    $sql = "
        SELECT f.id, f.name, f.price, f.image_url, f.description, 
               c.name AS category_name, r.restaurant_name, r.id AS restaurant_id
        FROM food_items f
        JOIN catagories c ON f.catagory_id = c.id
        JOIN restaurants r ON f.restaurant_id = r.id
        WHERE f.is_available = 1 AND r.status = 'Approved' AND ($where_sql)
        ORDER BY 
            CASE 
                WHEN f.name LIKE ? THEN 1
                WHEN f.description LIKE ? THEN 2
                ELSE 3
            END,
            f.name ASC
        LIMIT 6
    ";

    // Order ranking params
    $params[] = '%' . $query . '%';
    $params[] = '%' . $query . '%';

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $food_results = $stmt->fetchAll();

    echo json_encode([
        'query' => $query,
        'count' => count($restaurants) + count($food_results),
        'restaurants' => $restaurants,
        'results' => $food_results
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage(), 'restaurants' => [], 'results' => [], 'count' => 0]);
}
