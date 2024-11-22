    <?php
        
    include __DIR__ . '/vendor/autoload.php';

    use Phroute\Phroute\RouteCollector;
    use Phroute\Phroute\Dispatcher;
    use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
    use Phroute\Phroute\Exception\HttpRouteNotFoundException;
    
    $serveraddress = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sakila";
    $charset = "utf8";

    $dsn = "mysql:host=$serveraddress;dbname=$dbname;charset=$charset";

    $opt = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        ];
    $pdo = new PDO($dsn, $username, $password, $opt);

    
     function processInput($uri){
     $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    
     return $uri;
     }
    
     function processOutput($response){
        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    
    $router = new RouteCollector();
    
    $router->get('/customers', function () use ($pdo) {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perpage']) ? (int)$_GET['perpage'] : 10;
        $offset = ($page - 1) * $perPage;
        $filters = [];
        $params = [];

        if (isset($_GET['store_id'])) {
            $filters[] = 'store_id=:store_id';
            $params[':store_id'] = (int)$_GET['store_id'];
        }
    
        if (isset($_GET['active'])) {
            $filters[] = 'active=:active';
            $params[':active'] = (int)$_GET['active'];
        }

        $sql = "SELECT * FROM customer";
        
        if (!empty($filters)) {
            $sql .= " WHERE " . implode(' AND ', $filters);
        }
        if (isset($_GET['sort'])){
            $sort = $_GET['sort'];
            $sql .= " ORDER BY $sort";
        }
        
        $sql .= " LIMIT :offset, :perpage";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':perpage', $perPage, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }); 
    
    $router->get('/customers/{id}', function ($id) use ($pdo) { 
        $sql = "SELECT * FROM customer WHERE customer_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        } else {
            return ['error' => '400 Bad request', 'id' => "Wrong id: " . $id];
        }
    }); 

    $router->get('/stores', function () use ($pdo) { 
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perpage']) ? (int)$_GET['perpage'] : 10;
        $offset = ($page - 1) * $perPage;
        $filters = [];
        $params = [];

        if (isset($_GET['manager_staff_id'])) {
            $filters[] = 'manager_staff_id = :manager_staff_id';
            $params[':manager_staff_id'] = (int)$_GET['manager_staff_id'];
        }
        $sql = "SELECT * FROM store";
        if (!empty($filters)) {
            $sql .= " WHERE " . implode(' AND ', $filters);
        }
        $sql .= " LIMIT :offset, :perpage";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':perpage', $perPage, PDO::PARAM_INT);
        if (isset($_GET['sort'])){
            $sort = $_GET['sort'];
            $sql .= " ORDER BY $sort";
        }
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }); 
    
    $router->get('/stores/{id}', function ($id) use ($pdo) { 
        $sql = "SELECT * FROM store WHERE store_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        } else {
            return ['error' => '400 Bad request', 'id' => "Wrong id: " . $id];
        }
    }); 

    $router->get('/films', function () use ($pdo) { 
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perpage']) ? (int)$_GET['perpage'] : 10;
        $offset = ($page - 1) * $perPage;
        $filters = [];
        $params = [];

        if (isset($_GET['release_year'])) {
            $release_year = $_GET['release_year'];
            if (strpos($release_year, '>') !== false) {
                $filters[] = 'release_year > :release_year';
                $params[':release_year'] = (int)str_replace('>', '', $release_year);
            } elseif (strpos($release_year, '<') !== false) {
                $filters[] = 'release_year < :release_year';
                $params[':release_year'] = (int)str_replace('<', '', $release_year);
            }elseif (strpos($release_year, '<') !== false && strpos($release_year, '>') !== false) {
                list($min, $max) = explode('<', str_replace('>', '', $release_year));
                $filters[] = 'release_year BETWEEN :min_release_year AND :max_release_year';
                $params[':min_release_year'] = (int)$min;
                $params[':max_release_year'] = (int)$max;
            } else {
                $filters[] = 'release_year = :release_year';
                $params[':release_year'] = (int)$release_year;
            }
        }
        if (isset($_GET['language_id'])) {
            $languages = explode(',', $_GET['language_id']);
            $set = "\"".implode("\",\"",$languages)."\"";
            $filters[] = "language_id IN ($set)";
        }
    
        if (isset($_GET['original_language_id'])) {
            $filters[] = 'original_language_id = :original_language_id';
            $params[':original_language_id'] = (int)$_GET['original_language_id'];
        }
    
        if (isset($_GET['length'])) {
            $length = $_GET['length'];
            if (strpos($length, '>') !== false) {
                $filters[] = 'length > :length';
                $params[':length'] = (int)str_replace('>', '', $length);
            } elseif (strpos($length, '<') !== false) {
                $filters[] = 'length < :length';
                $params[':length'] = (int)str_replace('<', '', $length);
            }else{
                $filters[] = 'length = :length';
                $params[':length'] = (int)str_replace('=', '', $length);
            }
        }
    
        if (isset($_GET['rating'])) {
            $ratings = explode(',', $_GET['rating']);
            $set = "\"".implode("\",\"",$ratings)."\"";
            $filters[] = "rating IN ($set)";
        }
        print_r($_GET);
        $sql = "SELECT * FROM film";
        if (!empty($filters)) {
            $sql .= " WHERE " . implode(' AND ', $filters);
        }
        $sql .= " LIMIT :offset, :perpage";
        if (isset($_GET['sort'])){
            $sort = $_GET['sort'];
            $sql .= " ORDER BY $sort";
        }
        echo $sql."\n";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':perpage', $perPage, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $stmt->bindValue($key + 1, $value);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }); 
    
    $router->get('/films/{id}', function ($id) use ($pdo) { 
        $sql = "SELECT * FROM film WHERE film_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        } else {
            return ['error' => '400 Bad request', 'id' => "Wrong id: " . $id];
        } 
    }); 

    $router->get('/rentals', function () use ($pdo) { 
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perpage']) ? (int)$_GET['perpage'] : 10;
        $offset = ($page - 1) * $perPage;
        $filters = [];
        $params = [];
    
        if (isset($_GET['inventory_id'])) {
            $filters[] = 'inventory_id = :inventory_id';
            $params[':inventory_id'] = (int)$_GET['inventory_id'];
        }
    
        if (isset($_GET['customer_id'])) {
            $filters[] = 'customer_id = :customer_id';
            $params[':customer_id'] = (int)$_GET['customer_id'];
        }
    
        if (isset($_GET['return_date'])) {
            $return_date = $_GET['return_date'];
            if (strpos($return_date, '>') !== false) {
                echo ">\n";
                $filters[] = 'return_date > :return_date';
                $params[':return_date'] = str_replace('>', '', $return_date);
            } elseif (strpos($return_date, '<') !== false) {
                echo "<\n";

                $filters[] = 'return_date < :return_date';
                $params[':return_date'] = str_replace('<', '', $return_date);
            } elseif (strpos($return_date, '<') !== false && strpos($return_date, '>') !== false) {
                echo "<>\n";
                list($min, $max) = explode('<', str_replace('>', '', $return_date));
                $filters[] = 'return_date BETWEEN :min_return_date AND :max_return_date';
                $params[':min_return_date'] = $min;
                $params[':max_return_date'] = $max;
            } else{
                echo "<>\n";
                $filters[] = 'return_date = :return_date';
                $params[':return_date'] = $return_date;
            }
        }
        print_R($_GET);
        
        if (isset($_GET['staff_id'])) {
            $filters[] = 'staff_id = :staff_id';
            $params[':staff_id'] = (int)$_GET['staff_id'];
        }
        $sql = "SELECT * FROM rental";
        if (!empty($filters)) {
            $sql .= " WHERE " . implode(' AND ', $filters);
        }
        if (isset($_GET['sort'])){
            $sort = $_GET['sort'];
            $sql .= " ORDER BY $sort";
        }
        $sql .= " LIMIT :offset, :perpage";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':perpage', $perPage, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        echo $sql."\n";
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }); 
    
    $router->get('/rentals/{id}', function ($id) use ($pdo) { 
        $sql = "SELECT * FROM rental WHERE rental_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        } else {
            return ['error' => '400 Bad request', 'id' => "Wrong id: " . $id];
        }
    }); 
    
     $dispatcher = new Dispatcher($router->getData());
    
     try { 
        $response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], processInput($_SERVER['REQUEST_URI'])); 
    } catch (HttpRouteNotFoundException $e) { 
        $response = ['error' => '404 Not found', 'message' => $e->getMessage()]; 
    } catch (HttpMethodNotAllowedException $e) { 
        $response = ['error' => '400 Bad request', 'message' => $e->getMessage()]; 
    } 
    
     processOutput($response);
     unset($pdo);
    ?>
