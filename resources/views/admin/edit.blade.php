<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Employee</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<h1 class="text-3xl font-bold mb-6">Edit Employee</h1>

<form action="/update-employee/{{ $emp->id }}" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow">
    @csrf
    <div class="mb-3">
        <label>Name:</label>
        <input type="text" name="name" value="{{ $emp->name }}" class="border p-2 w-full" required>
    </div>
    <div class="mb-3">
        <label>Email:</label>
        <input type="email" name="email" value="{{ $emp->email }}" class="border p-2 w-full" required>
    </div>
    <div class="mb-3">
        <label>Specialization:</label>
        <input type="text" name="specialization" value="{{ $emp->specialization }}" class="border p-2 w-full" required>
    </div>
    <div class="mb-3">
        <label>Current Resume:</label>
        @if($emp->cv)
            <a href="{{ asset('uploads/cv/'.$emp->cv) }}" target="_blank" class="text-blue-500 underline">Download PDF</a>
        @else
            Not Available
        @endif
    </div>
    <div class="mb-3">
        <label>Change Resume (PDF):</label>
        <input type="file" name="cv" class="border p-2 w-full">
    </div>
    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update</button>
</form>

</body>
</html>
