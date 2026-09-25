# Pizi — Property Create / Update / Image Upload API

Base URL: `https://pizi.in/api` (use `https://test.pizi.in/api` while testing).
All endpoints below need an owner's bearer token:

```
Authorization: Bearer <token>
Accept: application/json
```

(Get the token via `POST /api/auth/login` with `{"identifier": "<phone>"}`, then
`POST /api/auth/otp/verify` with `{"identifier", "otp"}` — the response's
`data.token` is what goes in the header above.)

All three endpoints below accept **multipart/form-data** (so files and normal
fields go in the same request) — in curl that's just `-F` for every field.

---

## 1. Create a property — `POST /api/owner/properties`

### All accepted fields

| Field | Type | Notes |
|---|---|---|
| `name` | string | required |
| `city_id` | integer | required |
| `locality_id` | integer | required unless `locality_name` is sent |
| `locality_name` | string | **new locality** — if the locality isn't in the dropdown list yet, send its name here instead of `locality_id` and it gets created automatically (scoped to `city_id`) |
| `nearby_university_id` | integer | optional |
| `university_name` | string | **new university** — same idea: if it's not in the list yet, send a name here (+ optional `university_abbreviation`) and it gets created |
| `university_abbreviation` | string | optional, goes with `university_name` |
| `address_line` | string | required |
| `property_type` | `pg` \| `hostel` \| `coliving` \| `flatmate` | required |
| `gender` | `male` \| `female` \| `unisex` | required |
| `rent_min` / `rent_max` | number | required |
| `security_deposit` | number | optional |
| `description`, `rules` | string | optional |
| `food_included` | boolean | optional |
| `food_type` | `veg` \| `non_veg` \| `both` | optional |
| `food_timing[breakfast][timing]` | string, e.g. `"8-9AM"` | optional |
| `food_timing[breakfast][days]` | `all` \| `weekdays` \| `weekends` \| `none` | optional |
| `food_timing[lunch][timing]` / `[days]` | same as breakfast | optional |
| `food_timing[dinner][timing]` / `[days]` | same as breakfast | optional |
| `construction_year` | integer, e.g. `2020` | optional |
| `pet_allowed` | boolean | optional |
| `guest_entry_allowed` | boolean | optional |
| `nearby_police_station` | string | optional |
| `landmark` | string (free text, e.g. "Near XYZ Mall") | optional |
| `pincode` | string | optional |
| `latitude` / `longitude` | number | optional |
| `google_map_link` | string | optional |
| `total_rooms` / `available_rooms` | integer | optional |
| `sharing_single` / `sharing_double` / `sharing_triple` | number | optional — rent for single/double/triple sharing rooms. **Always send all three together** (even on update) — whichever ones you send become the complete list, so leaving one out removes it. |
| `meta_title` / `meta_description` | string | optional |
| `amenities[]` | integer (amenity ID), repeat the field for each one | optional — get valid IDs from `GET /api/amenities` |
| `landmarks[0][landmark_id]` | integer | optional — nearby metro/hospital/mall/university etc. |
| `landmarks[0][distance_km]` | number, e.g. `1.5` | goes with the landmark above; repeat `landmarks[1][...]`, `landmarks[2][...]` for more |
| `cover_image_file` | **file** (jpg/png, max 5MB) | the actual cover photo — preferred over `cover_image` |
| `cover_image` | string | only if you already have a hosted image URL/path instead of uploading a file |
| `images[]` | **file** (jpg/png, max 5MB each) | gallery photos — repeat the field for each photo, all upload in this one request |

### curl example

```bash
curl -X POST https://pizi.in/api/owner/properties \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "name=Sunrise PG" \
  -F "city_id=1" \
  -F "locality_id=11" \
  -F "address_line=123 Main Street, Sector 12" \
  -F "property_type=pg" \
  -F "gender=male" \
  -F "rent_min=8000" \
  -F "rent_max=10000" \
  -F "security_deposit=5000" \
  -F "food_included=1" \
  -F "food_type=veg" \
  -F "food_timing[breakfast][timing]=8-9AM" \
  -F "food_timing[breakfast][days]=all" \
  -F "food_timing[lunch][timing]=1-2PM" \
  -F "food_timing[lunch][days]=weekdays" \
  -F "food_timing[dinner][timing]=8-9PM" \
  -F "food_timing[dinner][days]=all" \
  -F "construction_year=2020" \
  -F "pet_allowed=0" \
  -F "guest_entry_allowed=1" \
  -F "sharing_single=9000" \
  -F "sharing_double=7000" \
  -F "sharing_triple=6000" \
  -F "nearby_police_station=Sector 12 Police Chowki" \
  -F "amenities[]=2" \
  -F "amenities[]=8" \
  -F "landmarks[0][landmark_id]=1" \
  -F "landmarks[0][distance_km]=1.5" \
  -F "cover_image_file=@/path/to/cover.jpg" \
  -F "images[]=@/path/to/photo1.jpg" \
  -F "images[]=@/path/to/photo2.jpg"
```

**Response**

```json
{"success": true, "data": {"id": 273, "slug": "sunrise-pg-abc123"}}
```

### If the locality or university isn't in your dropdown yet

Skip `locality_id` and send `locality_name` instead (same for `nearby_university_id` →
`university_name` + optional `university_abbreviation`). Both get auto-created:

```bash
  -F "city_id=1" \
  -F "locality_name=Sector 99, New Area" \
  -F "university_name=ABC Institute of Technology" \
  -F "university_abbreviation=ABCIT" \
```

---

## 2. Update a property — `POST /api/owner/properties/{id}`

Same fields as create, but **every field is optional** — only send what changed.
`amenities[]` and `landmarks[...]`, when sent, **replace** the existing list
(so send the full list each time, not just the new item to add).

```bash
curl -X POST https://pizi.in/api/owner/properties/273 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "rent_min=8500" \
  -F "pet_allowed=1" \
  -F "nearby_police_station=Updated Police Station" \
  -F "cover_image_file=@/path/to/new-cover.jpg"
```

**Response**

```json
{"success": true, "data": {"message": "Updated", "new_images": []}}
```

If you also attach `images[]` files in the same update call, they're added to
the gallery and returned in `new_images`.

---

## 3. Upload gallery photos — `POST /api/owner/properties/{id}/images`

Use this any time *after* the property already exists, separate from create/update.

**Single photo:**

```bash
curl -X POST https://pizi.in/api/owner/properties/273/images \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "image=@/path/to/photo.jpg"
```

Add `-F "is_cover=1"` to also set this photo as the property's cover image
in the same call.

**Multiple photos in one call:**

```bash
curl -X POST https://pizi.in/api/owner/properties/273/images \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "images[]=@/path/to/photo1.jpg" \
  -F "images[]=@/path/to/photo2.jpg" \
  -F "images[]=@/path/to/photo3.jpg"
```

**Response (single)**
```json
{"success": true, "data": {"id": 681, "url": "https://pizi.in/storage/properties/gallery/xxxx.jpg"}}
```

**Response (multiple)**
```json
{"success": true, "data": {"images": [{"id": 682, "url": "..."}, {"id": 683, "url": "..."}]}}
```

---

## 4. Delete a gallery photo — `DELETE /api/owner/properties/images/{imageId}`

```bash
curl -X DELETE https://pizi.in/api/owner/properties/images/682 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## Notes

- Max file size for any photo: **5 MB**. Accepted types: jpg/png/webp/etc (anything `image/*`).
- `amenities[]` IDs come from `GET /api/amenities` (no auth needed).
- `landmarks[N][landmark_id]` IDs come from the city's landmark list — ask if you need that
  endpoint exposed; for now these can be passed manually per city.
- Every response follows the same shape: `{"success": true/false, "data": {...}}` or
  `{"success": false, "errors": {...}}` (422) when validation fails.
