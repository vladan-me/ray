<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Cleaner</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f9; margin: 0; }
        .container { background: white; padding: 2rem 4rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center; }
        h1 { color: #333; }
        .upload-btn-wrapper { position: relative; overflow: hidden; display: inline-block; cursor: pointer; }
        .btn { border: 2px solid #007bff; color: #007bff; background-color: white; padding: 10px 20px; border-radius: 8px; font-size: 16px; font-weight: bold; transition: all 0.2s; }
        .upload-btn-wrapper:hover .btn { background-color: #007bff; color: white; }
        .upload-btn-wrapper input[type=file] { font-size: 100px; position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; }
        button[type="submit"] { background-color: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; margin-top: 20px; transition: background-color 0.2s; }
        button[type="submit"]:hover { background-color: #218838; }
    </style>
</head>
<body>
<div class="container">
    <h1>Upload Your CSV File</h1>
    <form action="{{ url('/process-csv') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="upload-btn-wrapper">
            <button class="btn">Choose file</button>
            <input type="file" name="csv_file" id="csv_file" onchange="document.getElementById('file-name').textContent = this.files[0].name" required>
        </div>
        <p id="file-name"></p>
        <button type="submit">Process and Download</button>
    </form>
</div>
</body>
</html>
