<?php

namespace App\Constants;

class Messages
{
    // Success messages
    const SUCCESS_PAYMENT_CREATED = 'Payment created successfully!';
    const SUCCESS_RECEIPT_CREATED = 'Receipt created successfully!';
    const SUCCESS_UPDATED = 'Data updated successfully!';
    const SUCCESS_DELETED = 'Data deleted successfully!';

    // Error messages
    const ERROR_PAYMENT_CREATION_FAILED = 'Payment creation failed, receipt has been rolled back.';
    const ERROR_RECEIPT_NOT_FOUND = 'Receipt not found!';
    const ERROR_INVALID_PAYMENT_MODE = 'Invalid payment mode!';
    const ERROR_RECORD_NOT_FOUND = 'Record not found!';
    const ERROR_INVALID_REQUEST = 'Invalid request data!';
    
    // Validation messages
    const ERROR_VALIDATION_FAILED = 'Validation failed! Please check the input data.';
    const ERROR_UNAUTHORIZED = 'You are not authorized to perform this action.';
    
    // Generic messages
    const ERROR_INTERNAL_SERVER = 'Something went wrong, please try again later.';
    const SUCCESS_OPERATION_COMPLETED = 'Operation completed successfully.';
}