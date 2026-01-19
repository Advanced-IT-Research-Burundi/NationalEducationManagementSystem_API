<?php

use Illuminate\Support\Facades\Schema;


/**
 * Send a JSON response with a success message.
 *
 * @param mixed $data The data to include in the response
 * @param string $message The success message
 * @param int $code The HTTP status code (default: 200)
 * @return \Illuminate\Http\JsonResponse
 */
function sendResponse($data, $message, $code = 200)
{
    $response = [
        'success' => true,
        'data' => $data,
        'message' => $message,
    ];

    return response()->json($response, $code);
}


/**
 * Send a JSON response with an error message.
 *
 * @param string $message The error message
 * @param array $errors The error details (default: [])
 * @param int $code The HTTP status code (default: 400)
 * @return \Illuminate\Http\JsonResponse
 */
function sendError($message, $errors = [], $code = 400)
{
    $response = [
        'success' => false,
        'message' => $message,
        'errors' => $errors,
    ];

    return response()->json($response, $code);
}

function addColumnIfNotExists($table, $column, $type, $after = null)
{
    if (!Schema::hasColumn($table, column: $column)) {
        Schema::table($table, function ($table) use ($column, $type, $after) {
            $columnDefinition = $table->$type($column)->nullable();
            if ($after) {
                $columnDefinition->after($after);
            }
        });
    }
}

const EMAIL_COPIES = [
    'irumvabric@gmail.com'
];

