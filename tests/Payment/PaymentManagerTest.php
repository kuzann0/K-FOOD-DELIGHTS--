<?php
namespace KFoodDelights\Payment\Tests;

require_once __DIR__ . '/../../vendor/autoload.php';
use PHPUnit\Framework\TestCase;
use KFoodDelights\Payment\PaymentManager;

class PaymentManagerTest extends TestCase
{
    private $paymentManager;
    
    protected function setUp(): void
    {
        $this->paymentManager = PaymentManager::getInstance();
    }
    
    public function testProcessPaymentWithGCash()
    {
        $orderId = 1;
        $paymentData = [
            'gateway' => 'gcash',
            'phone_number' => '09123456789',
            'amount' => 1000.00
        ];
        
        $result = $this->paymentManager->processPayment($orderId, $paymentData);
        
        $this->assertArrayHasKey('payment_id', $result);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertEquals('success', $result['status']);
    }
    
    public function testProcessPaymentWithInvalidAmount()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $orderId = 1;
        $paymentData = [
            'gateway' => 'gcash',
            'phone_number' => '09123456789',
            'amount' => -100.00
        ];
        
        $this->paymentManager->processPayment($orderId, $paymentData);
    }
    
    public function testProcessPaymentWithNonexistentOrder()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order not found');
        
        $orderId = 99999;
        $paymentData = [
            'gateway' => 'gcash',
            'phone_number' => '09123456789',
            'amount' => 1000.00
        ];
        
        $this->paymentManager->processPayment($orderId, $paymentData);
    }
    
    public function testProcessRefund()
    {
        $paymentId = 1;
        $amount = 500.00;
        $reason = 'Customer request';
        
        $result = $this->paymentManager->processRefund($paymentId, $amount, $reason);
        
        $this->assertArrayHasKey('refund_id', $result);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertEquals('success', $result['status']);
    }
    
    public function testProcessRefundWithInvalidAmount()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Refund amount cannot exceed payment amount');
        
        $paymentId = 1;
        $amount = 10000.00; // Amount greater than original payment
        
        $this->paymentManager->processRefund($paymentId, $amount);
    }
}