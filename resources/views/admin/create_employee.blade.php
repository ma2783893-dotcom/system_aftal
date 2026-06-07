<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Employee</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Add Employee</h1>
        <a href="/" class="text-blue-500 underline hover:text-blue-700">Back to Dashboard</a>
    </div>

    <form action="/add-employee" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow-md">
        @csrf
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Name:</label>
            <input type="text" name="name" class="border p-2 w-full rounded focus:outline-none focus:border-blue-500" required>
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Email:</label>
            <input type="email" name="email" class="border p-2 w-full rounded focus:outline-none focus:border-blue-500" required>
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Specialization:</label>
            <input type="text" name="specialization" class="border p-2 w-full rounded focus:outline-none focus:border-blue-500" required>
        </div>
        <div class="mb-6">
            <label class="block mb-2 font-semibold">Upload Resume (PDF):</label>
            <input type="file" name="cv" class="border p-2 w-full rounded" accept=".pdf">
        </div>
        <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600 transition shadow">Add Employee</button>
    </form>
</div>

</body>
</html>
