<?php
class DataController {
    private $sampleData = [
        1 => ['id' => 1, 'title' => 'First Item', 'description' => 'Description of first item'],
        2 => ['id' => 2, 'title' => 'Second Item', 'description' => 'Description of second item'],
        3 => ['id' => 3, 'title' => 'Third Item', 'description' => 'Description of third item']
    ];
    
    public function getData() {
        echo json_encode([
            'success' => true,
            'data' => array_values($this->sampleData)
        ]);
    }
    
    public function getDataById($id) {
        if (isset($this->sampleData[$id])) {
            echo json_encode([
                'success' => true,
                'data' => $this->sampleData[$id]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Item not found']);
        }
    }
    
    public function createData() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['title']) || !isset($data['description'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and description are required']);
            return;
        }
        
        $newId = max(array_keys($this->sampleData)) + 1;
        $newItem = [
            'id' => $newId,
            'title' => $data['title'],
            'description' => $data['description']
        ];
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => $newItem
        ]);
    }
    
    public function updateData($id) {
        if (!isset($this->sampleData[$id])) {
            http_response_code(404);
            echo json_encode(['error' => 'Item not found']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        $updatedItem = $this->sampleData[$id];
        
        if (isset($data['title'])) {
            $updatedItem['title'] = $data['title'];
        }
        
        if (isset($data['description'])) {
            $updatedItem['description'] = $data['description'];
        }
        
        
        echo json_encode([
            'success' => true,
            'data' => $updatedItem
        ]);
    }
    
    public function deleteData($id) {
        if (!isset($this->sampleData[$id])) {
            http_response_code(404);
            echo json_encode(['error' => 'Item not found']);
            return;
        }
        
        http_response_code(204);
    }
}
