{{--
    ============================================================
    DASHBOARD GUIDE — CONTENT SOURCE
    ============================================================
    Yeh EK FILE hai jo popup AUR printable/PDF guide DONO mein
    use hoti hai. Jab bhi koi naya feature add ho, bas neeche wale
    $guideSections array mein ek naya item add kar do — popup aur
    PDF dono automatically update ho jaayenge, alag se kahi aur
    edit nahi karna padega.

    Har section mein: icon, title (EN/HI), body (EN/HI).
    ============================================================
--}}
@php
    $guideSections = [
        [
            'icon' => '🏠',
            'title_en' => 'Properties',
            'title_hi' => 'प्रॉपर्टी',
            'body_en' => 'Add and manage your PG listings — photos, pricing, amenities, and location. This is what tenants see on the public website.',
            'body_hi' => 'Apni PG listings add aur manage karo — photos, pricing, amenities, aur location. Yehi tenants ko public website pe dikhta hai.',
        ],
        [
            'icon' => '👥',
            'title_en' => 'My Tenants',
            'title_hi' => 'माय टेनेंट्स',
            'body_en' => 'Add a walk-in tenant directly — select their room/bed, and they get instant dashboard access via OTP login. Upload KYC documents anytime from their profile.',
            'body_hi' => 'Walk-in tenant seedha yahi se add karo — unka room/bed select karo, unhe turant OTP se apna dashboard access mil jaayega. KYC documents kabhi bhi unke profile se upload kar sakte ho.',
        ],
        [
            'icon' => '🛏️',
            'title_en' => 'Rooms & Beds',
            'title_hi' => 'रूम्स एंड बेड्स',
            'body_en' => 'Set up rooms with amenities (AC, WiFi, etc.), auto-create beds, and assign/unassign tenants. Deleted a room by mistake? Check "Trash" to restore it within 30 days.',
            'body_hi' => 'Rooms banao amenities ke saath (AC, WiFi, waghera), beds auto-create karo, tenants assign/unassign karo. Galti se room delete ho gaya? "Trash" mein 30 din tak restore kar sakte ho.',
        ],
        [
            'icon' => '💰',
            'title_en' => 'Rent Collection',
            'title_hi' => 'रेंट कलेक्शन',
            'body_en' => 'Rent bills for all active tenants are generated automatically every month — you don\'t need to click anything. Record cash/bank payments here. Online (UPI/Card) payments are handled by Pizi and settled to you.',
            'body_hi' => 'Sabhi active tenants ke rent bills har mahine automatically ban jaate hain — kuch click karne ki zaroorat nahi. Cash/bank payments yahin record karo. Online (UPI/Card) payments Pizi ke through handle hote hain aur aapko settle kiye jaate hain.',
        ],
        [
            'icon' => '📄',
            'title_en' => 'Agreements',
            'title_hi' => 'एग्रीमेंट्स',
            'body_en' => 'Rental agreements are e-signed by tenants using Aadhaar OTP — legally valid, no paperwork needed.',
            'body_hi' => 'Rental agreements tenants khud Aadhaar OTP se e-sign karte hain — legally valid hai, koi kaagzi kaam nahi chahiye.',
        ],
        [
            'icon' => '🎯',
            'title_en' => 'Leads',
            'title_hi' => 'लीड्स',
            'body_en' => 'People interested in your PG show up here. Unlock a lead (uses credits) to see their contact details and follow up.',
            'body_hi' => 'Jo log aapki PG mein interested hain wo yahan dikhte hain. Lead unlock karo (credits use hoti hai) unka contact dekhne aur follow-up karne ke liye.',
        ],
        [
            'icon' => '🛠️',
            'title_en' => 'Complaints',
            'title_hi' => 'कंप्लेंट्स',
            'body_en' => 'Tenants raise maintenance/other issues here. Mark them In Progress or Resolved as you handle them.',
            'body_hi' => 'Tenants maintenance ya doosri problems yahan raise karte hain. Jaise-jaise handle karo, "In Progress" ya "Resolved" mark karte jao.',
        ],
        [
            'icon' => '👤',
            'title_en' => 'PG Managers',
            'title_hi' => 'पीजी मैनेजर्स',
            'body_en' => 'Give a staff member limited access (e.g. only Tenants + Rooms, not Wallet) so you don\'t have to handle everything yourself.',
            'body_hi' => 'Kisi staff member ko limited access do (jaise sirf Tenants + Rooms, Wallet nahi) taaki sab kuch khud handle na karna pade.',
        ],
        [
            'icon' => '💳',
            'title_en' => 'Wallet & Credits',
            'title_hi' => 'वॉलेट और क्रेडिट्स',
            'body_en' => 'Credits are used to unlock leads. Buy more anytime from "Buy Credits" — secure payment via Razorpay.',
            'body_hi' => 'Leads unlock karne ke liye credits use hoti hain. "Buy Credits" se kabhi bhi aur khareed sakte ho — Razorpay se secure payment.',
        ],
    ];

    $lang = $lang ?? 'en'; // default language if not passed in
@endphp