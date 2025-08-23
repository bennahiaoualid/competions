<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gemini Service Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">
                🧪 Gemini Service Test
            </h1>
            
            @if(isset($success) && $success)
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold text-green-800 mb-4">✅ Success!</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-medium text-green-700 mb-2">Generated Question:</h3>
                            <div class="bg-white border border-green-200 rounded-lg p-4">
                                <pre class="whitespace-pre-wrap text-sm text-gray-800">{{ $question }}</pre>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white border border-green-200 rounded-lg p-4">
                                <h4 class="font-medium text-green-700 mb-2">Tokens Used:</h4>
                                <p class="text-2xl font-bold text-green-600">{{ $tokens }}</p>
                            </div>
                            
                            <div class="bg-white border border-green-200 rounded-lg p-4">
                                <h4 class="font-medium text-green-700 mb-2">Cost:</h4>
                                <p class="text-2xl font-bold text-green-600">${{ number_format($cost, 4) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold text-red-800 mb-4">❌ Error!</h2>
                    <div class="bg-white border border-red-200 rounded-lg p-4">
                        <pre class="whitespace-pre-wrap text-sm text-red-800">{{ $error ?? 'Unknown error occurred' }}</pre>
                    </div>
                </div>
            @endif
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-800 mb-4">Test Information:</h3>
                <ul class="space-y-2 text-blue-700">
                    <li>• This route tests the Gemini LLM service directly</li>
                    <li>• Generates a simple math question with multiple choice answers</li>
                    <li>• Shows tokens used and cost calculation</li>
                    <li>• Displays any errors that occur during generation</li>
                </ul>
                
                <div class="mt-4">
                    <a href="/test" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        🔄 Refresh Test
                    </a>
                </div>
            </div>
        </div>
    </div>
    </body>
</html>
