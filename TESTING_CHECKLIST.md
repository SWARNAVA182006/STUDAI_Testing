# Phase 3 Implementation Checklist

## ✅ Completed Tasks

- [x] Install Razorpay SDK (`composer require razorpay/razorpay`)
- [x] Create payment configuration file (`config/payment.php`)
- [x] Create PaymentTransaction migration (31 fields, 4 indexes)
- [x] Implement PaymentTransaction model (153 lines, status management)
- [x] Implement PaymentGatewayService (433 lines, dual gateway)
- [x] Implement PaymentController (359 lines, 10 methods)
- [x] Add API routes (5 payment endpoints + 1 webhook)
- [x] Add web routes (2 PayU callback routes)
- [x] Update .env with payment gateway variables
- [x] Create SubscriptionPlanSeeder (5 plans: Free, Basic, Pro, Annual variants)
- [x] Run seeder (`php artisan db:seed --class=SubscriptionPlanSeeder`)
- [x] Create PaymentSuccessNotification (email + database channels)
- [x] Integrate notification into PaymentGatewayService

## 🔄 Pending Tasks (Before Testing)

### 1. Environment Setup
- [ ] Sign up for Razorpay test account (https://razorpay.com)
- [ ] Get Razorpay test credentials:
  - [ ] Test Key (`RAZORPAY_KEY=rzp_test_...`)
  - [ ] Test Secret (`RAZORPAY_SECRET=...`)
  - [ ] Webhook Secret (`RAZORPAY_WEBHOOK_SECRET=...`)
- [ ] Sign up for PayU test account (https://www.payu.in)
- [ ] Get PayU test credentials:
  - [ ] Merchant Key (`PAYU_MERCHANT_KEY=...`)
  - [ ] Merchant Salt (`PAYU_MERCHANT_SALT=...`)
- [ ] Update `.env` with real test credentials

### 2. Webhook Configuration
- [ ] Install ngrok for local webhook testing: `ngrok http 8000`
- [ ] Copy ngrok URL (e.g., `https://abc123.ngrok.io`)
- [ ] Configure Razorpay webhook:
  - [ ] Go to Razorpay Dashboard → Settings → Webhooks
  - [ ] Add webhook URL: `https://abc123.ngrok.io/api/webhooks/razorpay`
  - [ ] Select events: payment.authorized, payment.captured, payment.failed, refund.created
  - [ ] Copy webhook secret to `.env`

### 3. PayU Configuration
- [ ] Configure PayU success URL: `http://localhost:8000/payment/success`
- [ ] Configure PayU failure URL: `http://localhost:8000/payment/failure`
- [ ] Update PayU merchant dashboard with callback URLs

### 4. Queue Configuration
- [ ] Ensure queue worker is running: `php artisan queue:work`
- [ ] OR start Horizon: `php artisan horizon`
- [ ] Verify `QUEUE_CONNECTION=database` in `.env`

### 5. Mail Configuration (for notifications)
- [ ] Configure mail driver (log, smtp, or mailtrap)
- [ ] If using mailtrap:
  - [ ] Sign up at https://mailtrap.io
  - [ ] Get inbox credentials
  - [ ] Update `.env`:
    ```env
    MAIL_MAILER=smtp
    MAIL_HOST=smtp.mailtrap.io
    MAIL_PORT=2525
    MAIL_USERNAME=your_username
    MAIL_PASSWORD=your_password
    MAIL_FROM_ADDRESS=noreply@studai.career
    ```

## 🧪 Testing Workflow

### Phase 1: Razorpay Test (Modal Flow)

#### Frontend Setup (Test with API tool like Postman)
```javascript
// 1. Initiate Payment
POST http://localhost:8000/api/payment/initiate
Headers: 
  Authorization: Bearer {your_token}
  Content-Type: application/json
Body:
{
  "plan_id": 2,
  "gateway": "razorpay"
}

// Expected Response:
{
  "success": true,
  "message": "Payment order created successfully",
  "data": {
    "order_id": "order_...",
    "amount": 49900,
    "currency": "INR",
    "key": "rzp_test_...",
    "name": "StudAI Career",
    "description": "Basic Subscription",
    // ... checkout config
  }
}
```

#### Frontend Integration (Later in Phase 5)
```javascript
// 2. Load Razorpay Checkout
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

const options = {
  key: response.data.key,
  amount: response.data.amount,
  currency: response.data.currency,
  name: response.data.name,
  description: response.data.description,
  order_id: response.data.order_id,
  handler: function (response) {
    // 3. Verify Payment
    axios.post('/api/payment/razorpay/callback', {
      razorpay_order_id: response.razorpay_order_id,
      razorpay_payment_id: response.razorpay_payment_id,
      razorpay_signature: response.razorpay_signature
    }).then(result => {
      console.log('Payment successful', result.data);
      window.location.href = '/dashboard';
    });
  },
  prefill: {
    name: 'John Doe',
    email: 'john@example.com',
    contact: '9999999999'
  },
  theme: {
    color: '#ec4899'
  }
};

const rzp = new Razorpay(options);
rzp.open();
```

#### Database Verification
```sql
-- Check transaction created
SELECT * FROM payment_transactions ORDER BY created_at DESC LIMIT 1;

-- Check subscription activated
SELECT * FROM user_subscriptions WHERE user_id = {your_user_id};

-- Check notification sent
SELECT * FROM notifications WHERE notifiable_id = {your_user_id} ORDER BY created_at DESC LIMIT 1;
```

### Phase 2: PayU Test (Redirect Flow)

#### API Test
```javascript
// 1. Initiate Payment
POST http://localhost:8000/api/payment/initiate
Headers: 
  Authorization: Bearer {your_token}
  Content-Type: application/json
Body:
{
  "plan_id": 2,
  "gateway": "payu"
}

// Expected Response:
{
  "success": true,
  "message": "Payment order created successfully",
  "data": {
    "payment_url": "https://test.payu.in/_payment",
    "form_data": {
      "key": "...",
      "txnid": "TXN_...",
      "amount": "499.00",
      "productinfo": "Basic Subscription",
      "firstname": "John",
      "email": "john@example.com",
      "hash": "...",
      "surl": "http://localhost:8000/payment/success",
      "furl": "http://localhost:8000/payment/failure"
    }
  }
}
```

#### Frontend Form Submission (Later in Phase 5)
```html
<!-- 2. Create form and submit -->
<form id="payu-form" action="{{ $paymentUrl }}" method="POST">
  <input type="hidden" name="key" value="{{ $formData['key'] }}">
  <input type="hidden" name="txnid" value="{{ $formData['txnid'] }}">
  <input type="hidden" name="amount" value="{{ $formData['amount'] }}">
  <input type="hidden" name="productinfo" value="{{ $formData['productinfo'] }}">
  <input type="hidden" name="firstname" value="{{ $formData['firstname'] }}">
  <input type="hidden" name="email" value="{{ $formData['email'] }}">
  <input type="hidden" name="hash" value="{{ $formData['hash'] }}">
  <input type="hidden" name="surl" value="{{ $formData['surl'] }}">
  <input type="hidden" name="furl" value="{{ $formData['furl'] }}">
</form>

<script>
  document.getElementById('payu-form').submit();
</script>
```

### Phase 3: Webhook Test

#### Using Razorpay Test Events
1. Go to Razorpay Dashboard → Webhooks
2. Click "Test Webhook"
3. Select event: `payment.captured`
4. Send test payload
5. Check logs:
```bash
tail -f storage/logs/laravel.log
```

#### Manual Webhook Test with curl
```bash
# Generate signature
echo -n '{"event":"payment.captured"}' | openssl dgst -sha256 -hmac "YOUR_WEBHOOK_SECRET" | sed 's/^.* //'

# Send request
curl -X POST http://localhost:8000/api/webhooks/razorpay \
  -H "Content-Type: application/json" \
  -H "X-Razorpay-Signature: GENERATED_SIGNATURE" \
  -d '{"event":"payment.captured","payload":{"payment":{"entity":{"id":"pay_test123","order_id":"ORD_123456","amount":49900,"status":"captured"}}}}'
```

### Phase 4: Payment History Test

```javascript
// Get payment history
GET http://localhost:8000/api/payment/history
Headers:
  Authorization: Bearer {your_token}

// Get single transaction
GET http://localhost:8000/api/payment/transaction/1
Headers:
  Authorization: Bearer {your_token}
```

### Phase 5: Refund Test

```javascript
// Request refund
POST http://localhost:8000/api/payment/refund/1
Headers:
  Authorization: Bearer {your_token}
  Content-Type: application/json
Body:
{
  "amount": 499.00,
  "reason": "Customer request"
}

// Check database
SELECT * FROM payment_transactions WHERE id = 1;
// Should have status = 'refunded' or 'partially_refunded'
```

## 🔍 Debugging Checklist

### If Payment Fails
- [ ] Check Laravel logs: `storage/logs/laravel.log`
- [ ] Verify credentials in `.env`
- [ ] Check transaction in database: `payment_transactions`
- [ ] Verify `retry_count` field
- [ ] Check `error_message` field for details

### If Webhook Fails
- [ ] Verify ngrok is running
- [ ] Check webhook URL in Razorpay dashboard
- [ ] Verify webhook secret matches `.env`
- [ ] Check signature verification logs
- [ ] Test with manual curl request

### If Notification Not Sent
- [ ] Check queue worker is running
- [ ] Verify `QUEUE_CONNECTION=database` in `.env`
- [ ] Check `jobs` table for pending jobs
- [ ] Check `failed_jobs` table for failures
- [ ] Verify mail configuration

### If Subscription Not Activated
- [ ] Check `PaymentGatewayService::processSuccess()` logs
- [ ] Verify transaction status is 'success'
- [ ] Check `user_subscriptions` table
- [ ] Verify plan has `duration_days` field

## 📊 Success Criteria

- [ ] **Razorpay Payment**: Successfully create order, complete payment, verify callback
- [ ] **PayU Payment**: Successfully create order, redirect to PayU, handle success callback
- [ ] **Webhook**: Receive and process Razorpay webhook events
- [ ] **Subscription Activation**: User subscription created/updated with correct plan
- [ ] **Notification**: Email sent to user with transaction details
- [ ] **Database Notification**: In-app notification created
- [ ] **Payment History**: View transaction history with pagination
- [ ] **Transaction Details**: View single transaction with plan info
- [ ] **Refund**: Successfully process Razorpay refund, log PayU refund request

## 🚀 Next Steps After Testing

### Phase 5: Frontend Integration
- [ ] Create pricing page with plan cards
- [ ] Build Razorpay checkout modal component
- [ ] Build PayU redirect form component
- [ ] Create payment history page
- [ ] Create transaction details modal
- [ ] Add subscription status to dashboard
- [ ] Show remaining applications/credits in dashboard

### Phase 4: Advanced Features
- [ ] Trial period implementation
- [ ] Auto-renewal logic
- [ ] Subscription upgrade/downgrade
- [ ] Prorated billing calculation
- [ ] Failed payment retry automation
- [ ] Dunning management (recovery emails)

### Admin Dashboard Integration
- [ ] Payment analytics widget
- [ ] Revenue reports
- [ ] Gateway comparison metrics
- [ ] Subscription lifecycle tracking
- [ ] Refund management interface

## 📝 Testing Log Template

```markdown
## Test Session: [Date]

### Razorpay Test
- [ ] Order Creation: [PASS/FAIL] - Transaction ID: ___________
- [ ] Payment Modal: [PASS/FAIL]
- [ ] Callback Verification: [PASS/FAIL]
- [ ] Subscription Activation: [PASS/FAIL] - Subscription ID: ___________
- [ ] Email Notification: [PASS/FAIL]
- [ ] Database Notification: [PASS/FAIL]

### PayU Test
- [ ] Order Creation: [PASS/FAIL] - Transaction ID: ___________
- [ ] Redirect to PayU: [PASS/FAIL]
- [ ] Success Callback: [PASS/FAIL]
- [ ] Subscription Activation: [PASS/FAIL] - Subscription ID: ___________
- [ ] Email Notification: [PASS/FAIL]
- [ ] Database Notification: [PASS/FAIL]

### Webhook Test
- [ ] payment.authorized: [PASS/FAIL]
- [ ] payment.captured: [PASS/FAIL]
- [ ] payment.failed: [PASS/FAIL]
- [ ] refund.created: [PASS/FAIL]

### Refund Test
- [ ] Razorpay Refund: [PASS/FAIL] - Refund ID: ___________
- [ ] PayU Refund Request: [PASS/FAIL]

### Issues Found:
1. ___________________________________________
2. ___________________________________________

### Notes:
___________________________________________
```

---

**Status**: Ready for testing after credential setup  
**Estimated Setup Time**: 30 minutes  
**Estimated Testing Time**: 2-3 hours  
**Next Phase**: Phase 5 (Frontend Views & UI)
