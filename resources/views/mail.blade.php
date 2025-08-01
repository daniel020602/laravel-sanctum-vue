<!DOCTYPE html>
<html>
<head>
    <title>Reservation Confirmation</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body>
    <h1 class="text-2xl font-bold">Reservation Confirmation</h1>
    <div class="mt-4 bg-gray-100 p-4 rounded">
        <p class="mt-2">Thank you for your reservation!</p>
        <p class="mt-2">Your reservation code is: <strong>{{ $reservation_code }}</strong></p>
    </div>
</body>
</html>