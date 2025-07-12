# Working with Escaped JSON in API Responses

> **Note:** This is a reference document only. The actual implementation should be in your API files (like `api/portfolio.php`). Use the code examples below as guidance for your implementation.

## Understanding the Format

When you see a response like this:

```json
"certifications": "[{\"title\":\"Certificate of Recognition\",\"description\":\"\",\"date\":\"SY 2022-2023\",\"image\":\"uploads\\/certificates\\/2_cert_1747182348_0.jpg\"}]"
```

This is a JSON string that contains escaped JSON. This happens when JSON data is stored as a text field in a database.

## Handling in Postman

### Viewing in a Readable Format

1. In Postman, click on the "Tests" tab
2. Add this code:

```javascript
// Parse the escaped JSON for viewing
var data = pm.response.json();
if (data.certifications) {
    var parsedCerts = JSON.parse(data.certifications);
    console.log("Parsed certifications:", JSON.stringify(parsedCerts, null, 2));
}
```

3. Send your request
4. Check the Postman console (View > Show Postman Console) to see the properly formatted JSON

### Editing Certifications

To edit certifications:

#### 1. GET the current data and parse it

First retrieve the current data, then parse the JSON string:

```javascript
// JavaScript (front-end)
const response = await fetch('/api/user/2');
const data = await response.json();
const certifications = JSON.parse(data.certifications);

// Now you can modify the array
certifications[0].title = "Updated Certificate Title";

// Add a new certificate
certifications.push({
  title: "New Certificate",
  description: "Certificate description",
  date: "June 15, 2024",
  image: "uploads/certificates/new_cert.jpg"
});
```

#### 2. UPDATE with the modified data

When updating, stringify the array before sending:

```javascript
// Convert back to string format for sending
const updatedData = {
  certifications: JSON.stringify(certifications)
};

// Send the update request
const updateResponse = await fetch('/api/user/2', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer your_token_here'
  },
  body: JSON.stringify(updatedData)
});
```

## Implementing in Dashboard API

Rather than manually editing through Postman, you can implement proper certificate management directly in your dashboard API:

### Option 1: Add Direct Certificate Management Endpoints

Create specific endpoints to manage certificates:

```php
// GET /api/certificates/{user_id}
// Returns all certificates for a user as a properly formatted array
function getCertificates($userId) {
    // Get from database
    $certJson = getUserCertificatesFromDB($userId);
    
    // Parse the JSON string from DB into PHP array
    $certificates = json_decode($certJson, true);
    
    // Return as a proper JSON array (not escaped string)
    return [
        'success' => true,
        'certificates' => $certificates // Will be rendered as a proper JSON array
    ];
}

// POST /api/certificates/{user_id}
// Add a new certificate
function addCertificate($userId, $certificateData) {
    // Get existing certificates
    $certJson = getUserCertificatesFromDB($userId);
    $certificates = json_decode($certJson, true) ?: [];
    
    // Add new certificate
    $certificates[] = $certificateData;
    
    // Save back to database
    updateUserCertificatesInDB($userId, json_encode($certificates));
    
    return [
        'success' => true,
        'message' => 'Certificate added successfully'
    ];
}

// PUT /api/certificates/{user_id}/{cert_index}
// Update a specific certificate
function updateCertificate($userId, $index, $certificateData) {
    // Get existing certificates
    $certJson = getUserCertificatesFromDB($userId);
    $certificates = json_decode($certJson, true) ?: [];
    
    if (!isset($certificates[$index])) {
        return ['error' => 'Certificate not found'];
    }
    
    // Update certificate
    $certificates[$index] = $certificateData;
    
    // Save back to database
    updateUserCertificatesInDB($userId, json_encode($certificates));
    
    return [
        'success' => true,
        'message' => 'Certificate updated successfully'
    ];
}

// DELETE /api/certificates/{user_id}/{cert_index}
// Delete a certificate
function deleteCertificate($userId, $index) {
    // Get existing certificates
    $certJson = getUserCertificatesFromDB($userId);
    $certificates = json_decode($certJson, true) ?: [];
    
    if (!isset($certificates[$index])) {
        return ['error' => 'Certificate not found'];
    }
    
    // Remove certificate
    array_splice($certificates, $index, 1);
    
    // Save back to database
    updateUserCertificatesInDB($userId, json_encode($certificates));
    
    return [
        'success' => true,
        'message' => 'Certificate deleted successfully'
    ];
}
```

### Option 2: Add Certificate Management to User API

If you prefer to keep everything in the user API, you can extend it:

```php
// PUT /api/user/{user_id}/certificates/{cert_index}
// Update a specific certificate within the user's data
function updateUserCertificate($userId, $certIndex, $certData) {
    // Get user data
    $user = getUserFromDB($userId);
    
    // Parse certificates
    $certificates = json_decode($user->certificates, true) ?: [];
    
    // Update the specific certificate
    if (isset($certificates[$certIndex])) {
        $certificates[$certIndex] = $certData;
        
        // Save back to database (assuming certificates is a JSON field in users table)
        $user->certificates = json_encode($certificates);
        updateUserInDB($user);
        
        return ['success' => true];
    } else {
        return ['error' => 'Certificate index not found'];
    }
}
```

### Front-end Implementation

Your dashboard can then use these API endpoints:

```javascript
// Display certificates
async function loadCertificates(userId) {
    const response = await fetch(`/api/certificates/${userId}`);
    const data = await response.json();
    
    // Data now contains certificates as a proper array, not an escaped string
    const certificates = data.certificates;
    
    // Display in UI
    displayCertificatesInUI(certificates);
}

// Add a new certificate
async function addCertificate(userId, certData) {
    const response = await fetch(`/api/certificates/${userId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify(certData)
    });
    
    const result = await response.json();
    if (result.success) {
        // Refresh certificate list
        loadCertificates(userId);
    }
}

// Edit a certificate
async function editCertificate(userId, certIndex, updatedData) {
    const response = await fetch(`/api/certificates/${userId}/${certIndex}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify(updatedData)
    });
    
    const result = await response.json();
    if (result.success) {
        // Refresh certificate list
        loadCertificates(userId);
    }
}
```

## PHP Server-Side Handling

If you're working on the server side, you'd handle it like this:

```php
// When receiving data
$certificationsJson = $_POST['certifications']; // This is already a JSON string

// When retrieving from database to send in API response
$userData = [
  'id' => $user->id,
  'name' => $user->name,
  'certifications' => $user->certifications // This is stored as a JSON string in DB
];
echo json_encode($userData);

// When updating in database
$certifications = json_decode($_POST['certifications'], true); // Convert to PHP array
// Modify if needed
$jsonToStore = json_encode($certifications); // Convert back to JSON string
// Store $jsonToStore in database
```

## Testing in Postman

To test editing certifications in Postman:

1. Send a GET request to retrieve the current data
2. Copy the certifications string and decode it (using online tools or Postman's Tests tab)
3. Modify the JSON as needed
4. Re-encode it as a JSON string (with escaped quotes)
5. Send a PUT request with the updated data
