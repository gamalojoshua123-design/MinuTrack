<?php
// ai_helper.php
require_once __DIR__ . '/../config.php';

function askAI($prompt, $system = '')
{
    try {
        // Get API key from config
        $apiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : null;

        if (!$apiKey) {
            error_log('GROQ_API_KEY is empty. Expected value in environment or in the project root .env file.');
            return 'AI service is not configured. Create a .env file in ' . dirname(__DIR__) . ' containing GROQ_API_KEY=your_groq_key_here (see .env.example). Restart Apache after saving.';
        }

        // Check if API key looks valid
        if (strlen($apiKey) < 20) {
            error_log('GROQ_API_KEY appears to be invalid (too short)');
            return 'API key appears to be invalid. Please check your Groq API key.';
        }

        $prompt = trim((string)$prompt);
        $system = trim((string)$system);

        if ($prompt === '') {
            return 'Please provide a question to ask.';
        }

        $messages = [];

        if ($system !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => $system
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];

        $payload = [
            'model' => 'openai/gpt-oss-120b',
            'messages' => $messages,
            'max_tokens' => 300,
            'temperature' => 0.7
        ];

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        if ($response === false) {
            curl_close($ch);
            error_log('CURL Error: ' . $curlError);
            return 'Failed to connect to AI service: ' . $curlError;
        }
        
        curl_close($ch);

        // Log response for debugging
        error_log("Groq API Response Code: $httpCode");
        
        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $errorMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : 'Unknown error';
            error_log('Groq API HTTP Error: ' . $httpCode . ' - ' . $errorMsg);
            return 'AI service error: ' . $errorMsg;
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            error_log('Invalid API response - not an array');
            return 'Invalid response from AI service.';
        }

        if (!isset($data['choices'][0]['message']['content'])) {
            error_log('Invalid API response structure: ' . json_encode($data));
            return 'AI service returned an unexpected response.';
        }

        $result = $data['choices'][0]['message']['content'];
        
        // Clean up the response
        $result = trim($result);
        $result = preg_replace('/\*\*(.*?)\*\*/', '$1', $result);
        $result = preg_replace('/\*(.*?)\*/', '$1', $result);
        
        if (empty($result)) {
            return 'AI service returned an empty response.';
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log('askAI Exception: ' . $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}
?>