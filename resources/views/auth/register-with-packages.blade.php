@extends('layouts.app')
@section('title', 'Register & Buy Credits - Pizi')
@section('content')

<div class="min-h-screen bg-gradient-to-br from-coral-50 to-orange-50 py-12 px-4">
    <div class="max-w-4xl mx-auto">
        
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold text-gray-900 mb-4">Register & Buy Credits</h1>
            <p class="text-xl text-gray-600">Start listing your PG today</p>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            
            <!-- LEFT: PACKAGES -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Choose Package</h2>
                
                <div id="packages-list" class="space-y-3">
                    @foreach($packages as $pkg)
                        <div class="pkg-option p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-coral-500 hover:bg-coral-50 transition"
                             data-id="{{ $pkg->id }}" data-price="{{ $pkg->price_inr }}" data-credits="{{ $pkg->credits }}" data-name="{{ $pkg->name }}">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="font-bold text-gray-900">{{ $pkg->name }}</h3>
                                    <p class="text-sm text-gray-600">{{ $pkg->credits }} credits</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-coral-600">₹{{ number_format($pkg->price_inr) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- RIGHT: FORM -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    {{ auth()->check() ? 'Complete Your Payment' : 'Create Account' }}
                </h2>
                
                <!-- Success Message -->
                <div id="success-msg" class="hidden bg-green-100 text-green-800 p-4 rounded-lg mb-6">
                    ✅ <span id="success-text"></span>
                </div>

                <!-- Error Message -->
                <div id="error-msg" class="hidden bg-red-100 text-red-800 p-4 rounded-lg mb-6">
                    ❌ <span id="error-text"></span>
                </div>

                <!-- Selected Package -->
                <div id="selected-pkg" class="hidden bg-blue-50 p-4 rounded-lg mb-6 border-l-4 border-blue-500">
                    <p class="text-sm text-blue-600">Selected Plan:</p>
                    <p id="selected-name" class="font-bold text-lg text-blue-900"></p>
                    <p id="selected-price" class="text-xl font-bold text-blue-600"></p>
                </div>

                @auth
                    <p class="text-sm text-gray-600 mb-4">Logged in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }})</p>
                    <button type="button" onclick="handleRegister()" id="reg-btn" disabled 
                            class="w-full bg-coral-500 hover:bg-coral-600 disabled:opacity-50 text-white font-bold py-3 rounded-lg transition mt-6">
                        💳 Pay Now
                    </button>
                @else
                    <form id="reg-form" class="space-y-4">
                        @csrf

                        <div>
                            <input type="text" id="name" name="name" required placeholder="Full Name" 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-coral-500">
                        </div>

                        <div>
                            <input type="email" id="email" name="email" required placeholder="Email Address" 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-coral-500">
                        </div>

                        <div>
                            <input type="tel" id="phone" name="phone" required maxlength="10" placeholder="Phone (10-digit)" 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-coral-500">
                        </div>

                        <div>
                            <input type="password" id="password" name="password" required minlength="6" placeholder="Password" 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-coral-500">
                        </div>

                        <button type="button" onclick="handleRegister()" id="reg-btn" disabled 
                                class="w-full bg-coral-500 hover:bg-coral-600 disabled:opacity-50 text-white font-bold py-3 rounded-lg transition mt-6">
                            💳 Register & Pay
                        </button>
                    </form>

                    <p class="text-center text-sm text-gray-600 mt-6">
                        Already registered? <a href="/login" class="text-coral-600 font-bold hover:underline">Login here</a>
                    </p>
                @endauth
            </div>

        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    let selectedPackage = null;
    const csrf = document.querySelector('input[name="_token"]').value;

    document.querySelectorAll('.pkg-option').forEach(el => {
        el.addEventListener('click', function() {
            document.querySelectorAll('.pkg-option').forEach(e => e.classList.remove('border-coral-500', 'bg-coral-50'));
            this.classList.add('border-coral-500', 'bg-coral-50');
            selectedPackage = {
                id: this.dataset.id,
                name: this.dataset.name,
                price: this.dataset.price,
                credits: this.dataset.credits
            };
            document.getElementById('selected-pkg').classList.remove('hidden');
            document.getElementById('selected-name').innerText = selectedPackage.name + ' - ' + selectedPackage.credits + ' credits';
            document.getElementById('selected-price').innerText = '₹' + selectedPackage.price;
            document.getElementById('reg-btn').disabled = false;
        });
    });

    function showError(msg) {
        // CONVERT ANYTHING TO STRING SAFELY
        let errorText = '';
        try {
            if (typeof msg === 'string') {
                errorText = msg;
            } else if (typeof msg === 'object') {
                errorText = JSON.stringify(msg, null, 2);
            } else {
                errorText = String(msg);
            }
        } catch (e) {
            errorText = 'An error occurred';
        }
        
        document.getElementById('error-text').innerText = errorText;
        document.getElementById('error-msg').classList.remove('hidden');
        document.getElementById('success-msg').classList.add('hidden');
    }

    async function handleRegister() {
        document.getElementById('error-msg').classList.add('hidden');
        document.getElementById('success-msg').classList.add('hidden');

        if (!selectedPackage) {
            showError('Select a package');
            return;
        }

        const isLoggedIn = {{ auth()->check() ? 'true' : 'false' }};
        const btn = document.getElementById('reg-btn');

        if (isLoggedIn) {
            btn.disabled = true;
            btn.innerText = 'Processing...';
            await proceedToPayment(btn);
            return;
        }

        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const password = document.getElementById('password').value;

        if (!name || !email || !phone || !password) {
            showError('Fill all fields');
            return;
        }
        if (phone.length !== 10) {
            showError('Phone: 10 digits');
            return;
        }

        btn.disabled = true;
        btn.innerText = 'Processing...';

        try {
            // REGISTRATION
            const regRes = await fetch('/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    name, email, phone, password, password_confirmation: password, role: 'owner'
                })
            });

            const regData = await regRes.json();

            // IF NOT SUCCESS - SHOW ERROR
            if (!regData.success) {
                showError(regData.message || 'Registration failed');
                btn.disabled = false;
                btn.innerText = '💳 Register & Pay';
                return;
            }

            await proceedToPayment(btn);

        } catch (error) {
            showError('Error: ' + error.message);
            btn.disabled = false;
            btn.innerText = '💳 Register & Pay';
        }
    }

    async function proceedToPayment(btn) {
        const nameEl = document.getElementById('name');
        const emailEl = document.getElementById('email');
        const phoneEl = document.getElementById('phone');

        const name = nameEl ? nameEl.value.trim() : '{{ auth()->user()->name ?? "" }}';
        const email = emailEl ? emailEl.value.trim() : '{{ auth()->user()->email ?? "" }}';
        const phone = phoneEl ? phoneEl.value.trim() : '{{ auth()->user()->phone ?? "" }}';

        try {
            // PAYMENT ORDER
            const orderRes = await fetch('/owner/purchase-package', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    package_id: selectedPackage.id,
                    payment_method: 'razorpay'
                })
            });

            const orderData = await orderRes.json();

            if (!orderData.success) {
                showError(orderData.message || 'Order failed');
                btn.disabled = false;
                btn.innerText = '💳 Pay Now';
                return;
            }

            // RAZORPAY
            new Razorpay({
                key: '{{ env("RAZORPAY_KEY_ID") }}',
                amount: orderData.amount,
                currency: orderData.currency,
                order_id: orderData.order_id,
                handler: async (response) => {
                    const verifyRes = await fetch('/owner/verify-payment', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_signature: response.razorpay_signature,
                            package_id: selectedPackage.id
                        })
                    });

                    const verifyData = await verifyRes.json();
                    
                    if (verifyData.success) {
                        alert('✅ Success!');
                        window.location = '/owner';
                    } else {
                        showError(verifyData.message || 'Verification failed');
                        btn.disabled = false;
                        btn.innerText = '💳 Pay Now';
                    }
                },
                prefill: {name, email, contact: phone},
                theme: {color: '#FF6B5B'}
            }).open();

        } catch (error) {
            showError('Error: ' + error.message);
            btn.disabled = false;
            btn.innerText = '💳 Pay Now';
        }
    }
</script>

@endsection