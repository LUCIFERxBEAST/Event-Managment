<?php
// ai_generator.php
header('Content-Type: application/json');

// 1. Get the Input (JSON)
$input = json_decode(file_get_contents('php://input'), true);
$title = isset($input['title']) ? trim($input['title']) : '';

if (empty($title)) {
    echo json_encode(["status" => "error", "message" => "No title provided"]);
    exit();
}

// 2. Simple "Keyword AI" Logic
// Real AI would use OpenAI API, but this is free and fast for localhost.

$response = [
    "description" => "",
    "tags" => [],
    "venue_suggestion" => "Tech Park Auditorium"
];

$t = strtolower($title);

if (strpos($t, 'hack') !== false || strpos($t, 'code') !== false) {
    $response['description'] .= "🚀 **Join the ultimate coding battle!**\n\nBuild solutions, solve problems, and win big prizes. Open to all developers.";
    $response['tags'][] = "Web Dev";
    $response['tags'][] = "App Dev";
}

if (strpos($t, 'ai') !== false || strpos($t, 'intel') !== false || strpos($t, 'gpt') !== false) {
    $response['description'] = "🤖 **Explore the Future of AI!**\n\nCreate the next generation of intelligent apps using LLMs and Neural Networks. \n\n🏆 Prizes: $1000 for best AI Model.";
    $response['tags'][] = "AI/ML";
}

if (strpos($t, 'design') !== false || strpos($t, 'ui') !== false) {
    $response['description'] = "🎨 **Where Creativity Meets Code.**\n\nDesign the most intuitive and beautiful user interfaces in this 24-hour design-a-thon.";
    $response['tags'][] = "Design";
}

if (strpos($t, 'game') !== false) {
    $response['description'] = "🎮 **Game On!**\n\nBuild an indie game in 48 hours. Unity, Unreal, or Godot - bring your engine and your creativity.";
    $response['tags'][] = "Game Dev";
}

// Default fallback if no keywords match
if (empty($response['description'])) {
    $response['description'] = "👋 **Welcome to $title!**\n\nAn exciting event for tech enthusiasts. Join us for learning, networking, and building amazing projects.";
    $response['tags'][] = "General";
}

// Add standard footer
$response['description'] .= "\n\n🍕 **Perks:** Free Food, Swag, and Wi-Fi.\n📅 **Schedule:** 9 AM - 9 PM.";

echo json_encode($response);
?>