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

    $conn = new mysqli($serveraddress, $username, $password,$dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
      }

    
     function processInput($uri){
     $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    
     return $uri;
     }
    
     function processOutput($response){
        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    function fetchData($conn, $table, $id = null) { 
        $table = mysqli_real_escape_string($conn, $table); 
     
        if ($id === null) { 
            $sql = "SELECT * FROM $table"; 
            $result = mysqli_query($conn, $sql); 
            return mysqli_fetch_all($result, MYSQLI_ASSOC); 
        } else { 
            $customId = $table . "_id"; 
            $stmt = mysqli_prepare($conn, "SELECT * FROM $table WHERE $customId = ?"); 
            mysqli_stmt_bind_param($stmt, 'i', $id); 
            mysqli_stmt_execute($stmt); 
            $result = mysqli_stmt_get_result($stmt); 
     
            if ($row = mysqli_fetch_assoc($result)) { 
                return $row; 
            } else { 
                return ['error' => '404 Not found', 'id' => "Wrong id: ".$id]; 
            } 
        } 
    } 
    
    $router = new RouteCollector();
    
    $router->get('/customer', function () use ($conn) { 
        return fetchData($conn, 'customer'); 
    }); 
    
    $router->get('/customer/{id}', function ($id) use ($conn) { 
        return fetchData($conn, 'customer', $id); 
    }); 

    $router->get('/store', function () use ($conn) { 
        return fetchData($conn, 'store'); 
    }); 
    
    $router->get('/store/{id}', function ($id) use ($conn) { 
        return fetchData($conn, 'store', $id); 
    }); 

    $router->get('/film', function () use ($conn) { 
        return fetchData($conn, 'film'); 
    }); 
    
    $router->get('/film/{id}', function ($id) use ($conn) { 
        return fetchData($conn, 'film', $id); 
    }); 

    $router->get('/rental', function () use ($conn) { 
        return fetchData($conn, 'rental'); 
    }); 
    
    $router->get('/rental/{id}', function ($id) use ($conn) { 
        return fetchData($conn, 'rental', $id); 
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
     $conn->close();
    ?>
