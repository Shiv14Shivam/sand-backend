# 4.2 Testing OR Verification Plan

After project work is complete, it must have some verification criterion so that we can decide whether the project was satisfactorily completed or not. This is called Testing or Verification. For example, in software development, some test case must be included and used to verify the outcome of the project.

---

## Module 1 — Authentication

| Test ID | Test Case Title | Test Condition | System Behavior | Expected Result |
|---------|----------------|----------------|-----------------|-----------------|
| T01 | Customer Registration | A new user submits valid name, email, phone, and password with role = customer | System creates the account and sends a verification email | Registration success message returned; user record created in DB |
| T02 | Vendor Registration | A new user submits valid details including firm name, business type, GST number with role = vendor | System creates vendor account with extra firm fields | Registration success; vendor profile created with firm metadata |
| T03 | Login with Valid Credentials | Registered user submits correct email and password | System authenticates user and issues a Sanctum Bearer token | HTTP 200; token returned; user data returned with correct role |
| T04 | Login with Wrong Password | User submits correct email but incorrect password | System rejects credentials | HTTP 401; error message "Invalid Credentials" |
| T05 | Role Mismatch Login | Customer tries to log in via vendor login screen | System detects role mismatch even if credentials are correct | HTTP 200 from server but app returns "Invalid Credentials" |
| T06 | Logout | Authenticated user calls logout endpoint | System invalidates the token server-side | HTTP 200; token deleted from server; SharedPreferences cleared on device |
| T07 | Access Protected Route Without Token | Unauthenticated request hits `/api/profile` | System rejects the request | HTTP 401 Unauthenticated |

---

## Module 2 — Address Management

| Test ID | Test Case Title | Test Condition | System Behavior | Expected Result |
|---------|----------------|----------------|-----------------|-----------------|
| T08 | Add Delivery Address | Customer submits label, address lines, city, state, pincode, and GPS coordinates | System stores address linked to customer | HTTP 201; address appears in address list |
| T09 | Set Default Address | Customer marks one of their saved addresses as default | System sets is_default=true on selected address and clears others | HTTP 200; only one address has is_default=true |
| T10 | Get Default Address Without Coordinates | Default address exists but has no latitude/longitude | System detects missing coordinates | Response returns success=false with message "Default address has no coordinates" |
| T11 | Delete Address | Customer deletes a saved address | System removes address record | HTTP 200; address no longer in list |

---

## Module 3 — Marketplace & Cart

| Test ID | Test Case Title | Test Condition | System Behavior | Expected Result |
|---------|----------------|----------------|-----------------|-----------------|
| T12 | Browse Marketplace | Customer views available sand listings without logging in | System returns public listing data | HTTP 200; list of active listings with price, stock, vendor info |
| T13 | Add Item to Cart | Customer adds a listing with quantity to their cart | System creates or updates cart item | HTTP 201; item appears in cart with correct subtotal |
| T14 | Update Cart Quantity | Customer changes quantity of existing cart item | System recalculates subtotal | HTTP 200; updated quantity and subtotal returned |
| T15 | Remove Cart Item | Customer removes an item from the cart | System deletes that cart record | HTTP 200; item gone from cart |
| T16 | Clear Entire Cart | Customer clears all items from cart | System deletes all cart records for that customer | HTTP 200; cart is empty |

---

## Module 4 — Order Placement

| Test ID | Test Case Title | Test Condition | System Behavior | Expected Result |
|---------|----------------|----------------|-----------------|-----------------|
| T17 | Place Direct Order | Customer selects a listing, quantity, and delivery address, and places order directly | System creates order and order items; calculates delivery charge using Haversine distance | HTTP 201; order created with status=pending, payment_status=unpaid |
| T18 | Place Order from Cart | Customer checks out all cart items as one order | System creates one order with multiple order items, one per vendor | HTTP 201; all order items created; cart emptied |
| T19 | Place Order — Out of Stock | Customer tries to order more units than available stock | System rejects the request | HTTP 422; message "Insufficient stock" |
| T20 | Place Order — No Default Address | Customer has no delivery address saved | System rejects before reaching the placement step | App shows address form; order not submitted |
| T21 | Cancel Pending Order | Customer cancels an order that is still in pending status | System sets status=cancelled; stock is not affected (not yet accepted) | HTTP 200; order status updated to cancelled |

---

## Module 5 — Vendor Order Management

| Test ID | Test Case Title | Test Condition | System Behavior | Expected Result |
|---------|----------------|----------------|-----------------|-----------------|
| T22 | View Incoming Orders | Vendor opens order list | System returns all order items assigned to that vendor with customer and delivery info | HTTP 200; list with status, amounts, customer details |
| T23 | Accept Order — Sufficient Stock | Vendor accepts a pending order item with enough stock | System updates status to accepted; payment_status=unpaid; **stock is NOT deducted yet** | HTTP 200; order status = accepted; listing stock unchanged |
| T24 | Accept Order — Insufficient Stock | Vendor tries to accept when listing stock < order quantity | System rejects | HTTP 422; message "Insufficient stock. Requested X unit, only Y available" |
| T25 | Decline Order | Vendor declines a pending order with a reason | System sets status=declined; stock unaffected | HTTP 200; order declined; customer notified |
| T26 | Accept Already-Accepted Order | Vendor tries to accept an order already in accepted status | System rejects duplicate action | HTTP 422; "This order item has already been accepted" |

---

## Module 6 — Payment (Pay Now via Razorpay)

| Test ID | Test Case Title | Test Condition | System Behavior | Expected Result |
|---------|----------------|----------------|-----------------|-----------------|
| T27 | Successful Direct Payment | Customer pays correct amount for an accepted order using Razorpay | Backend fetches payment from Razorpay, verifies status=captured, verifies amount and currency | HTTP 200; order status = processing; payment_status = paid; stock deducted from listing; both parties notified |
| T28 | Amount Mismatch Attack | Attacker sends a Razorpay payment_id for ₹1 against a ₹50,000 order | Backend calculates expected paise vs paid paise; difference > 100 paise | HTTP 422; "Payment amount mismatch"; warning logged in laravel.log |
| T29 | Fake Payment ID | Attacker sends a fabricated payment_id that does not exist on Razorpay | Razorpay SDK throws exception on fetch | HTTP 500 (debug mode) or generic failure message (production) |
| T30 | Wrong Currency | Payment made in USD or other non-INR currency | Backend checks `$payment->currency !== 'INR'` | HTTP 422; "Invalid currency. Expected INR" |
| T31 | Replay Attack — Reuse Payment ID | Attacker reuses a valid payment_id for a second order | Target order is already marked payment_status=paid | HTTP 422; "This order has already been paid" |
| T32 | Pay Someone Else's Order | Customer B sends a valid payment_id for Customer A's order item ID | `findCustomerItem()` returns null — no match for that customer | HTTP 404; "Order not found" |
| T33 | Pay a Pending Order (Not Yet Accepted) | Customer tries to pay before vendor accepts | Status guard: only accepted or processing orders can be paid | HTTP 422; "Payment not allowed for orders with status: pending" |
| T34 | Stock Deduction on Direct Payment | Customer pays now for accepted order | After payment confirmed, listing stock is decremented | Listing `available_stock_unit` reduced by `quantity_unit` |
| T35 | Listing Auto-Deactivate When Stock = 0 | Payment causes listing stock to reach 0 | System auto-sets listing status=inactive | Listing no longer appears in marketplace |

---

## Module 7 — Pay Later Flow

| Test ID | Test Case Title | Test Condition | System Behavior | Expected Result |
|---------|----------------|----------------|-----------------|-----------------|
| T36 | Request Pay Later | Customer requests pay later on an accepted order with 1–7 days | System sets payment_status=pay_later; payment_due_at calculated; status stays accepted | HTTP 200; vendor notified; due date set correctly |
| T37 | Vendor Approves Pay Later | Vendor approves the pay-later request | System sets order status=processing; payment_status stays pay_later; stock deducted | HTTP 200; customer notified; stock reduced |
| T38 | Vendor Rejects Pay Later | Vendor rejects the pay-later request with a reason | System sets status=declined; payment_status=unpaid; payment_due_at cleared | HTTP 200; customer notified with rejection reason |
| T39 | Customer Pays After Pay-Later Approval | Customer pays before due date after vendor approved | Backend recognises wasPayLater=true; sets status=delivered; payment_status=paid; stock NOT deducted again | HTTP 200; order delivered; no double stock deduction |
| T40 | Request Pay Later on Already-Paid Order | Customer tries to request pay later after already paying | Payment status guard: already paid | HTTP 422; "This order has already been paid" |
| T41 | Request Pay Later Twice | Customer requests pay later a second time | Duplicate guard check | HTTP 422; "Pay later already requested. Due: [date]" |

---

## Module 8 — Inventory Management (Vendor)

| Test ID | Test Case Title | Test Condition | System Behavior | Expected Result |
|---------|----------------|----------------|-----------------|-----------------|
| T42 | View Inventory | Vendor opens inventory page | System returns all listings with stock summary and accepted/pending unit counts | HTTP 200; listings with inventory_summary block |
| T43 | Revenue in Inventory Detail | Vendor opens a specific listing's stats | `total_revenue` is calculated from `payment_status = paid` orders only | Revenue = sum of subtotals for paid orders only; not accepted/unpaid orders |
| T44 | Restock Listing | Vendor adds stock to a listing | System increments `available_stock_unit` | HTTP 200; new stock level returned |
| T45 | Restock Reactivates Inactive Listing | Vendor restocks a listing that was inactive due to zero stock | System detects status=inactive and sets it back to active | HTTP 200; listing reappears in marketplace |
| T46 | Update Listing Price | Vendor submits new price_per_unit | System updates the listing price | HTTP 200; new price confirmed |
| T47 | Low Stock Alert in Dashboard | Listing stock drops to ≤ 10 units but > 0 | Frontend `_lowCount` correctly reflects listings in low stock range | Amber warning alert shown on vendor dashboard |
| T48 | Out of Stock Alert in Dashboard | Listing stock drops to 0 | Frontend `_outCount` incremented; listing auto-deactivated | Red stock alert shown; listing removed from marketplace |

---

## Module 9 — Vendor Dashboard & Revenue

| Test ID | Test Case Title | Test Condition | System Behavior | Expected Result |
|---------|----------------|----------------|-----------------|-----------------|
| T49 | Revenue Shows Zero Before Any Payment | No orders have been paid yet | `payment_status = 'paid'` matches nothing | Revenue card shows ₹0 |
| T50 | Revenue Updates After Direct Payment | Customer completes a Razorpay payment | `payment_status` becomes paid; `order_item` key read correctly in Flutter | Revenue card increases by `subtotal + delivery_charge` of that order |
| T51 | Revenue Excludes Pay-Later Unpaid Orders | Order is in processing but payment_status = pay_later | Pay-later order not counted until customer actually pays | Revenue unchanged until customer pays |
| T52 | Order Count Accuracy | Mixed orders across all statuses | Flutter reads `order_item.status` from API correctly after key fix | Pending, accepted, processing, delivered counts match DB |
| T53 | Revenue Includes Delivery Charge | Paid order has both subtotal and delivery_charge | Revenue = subtotal + delivery_charge per paid order | Revenue shows total amount received including delivery |

---

## Module 10 — Notifications

| Test ID | Test Case Title | Test Condition | System Behavior | Expected Result |
|---------|----------------|----------------|-----------------|-----------------|
| T54 | Vendor Notified on Order Placed | Customer places order | `OrderStatusUpdatedNotification` sent to vendor | Vendor sees new notification in notification list |
| T55 | Customer Notified on Vendor Accept/Decline | Vendor accepts or declines order | Notification sent to customer with updated status | Customer sees notification with order status |
| T56 | Vendor Notified on Pay Later Request | Customer requests pay later | `PayLaterRequestedNotification` sent to vendor | Vendor sees request with due date and order details |
| T57 | Customer Notified on Pay Later Decision | Vendor approves or rejects pay later | `PayLaterDecisionNotification` sent to customer | Customer sees approval or rejection with reason |
| T58 | Customer and Vendor Notified on Payment | Customer completes payment | `PaymentConfirmedNotification` sent to both parties | Both see payment confirmation with order and amount |
| T59 | Mark All Notifications Read | User taps "mark all read" | System sets all notifications to read | Unread count drops to 0; notifications no longer highlighted |

---

## Summary Table

| Module | Total Test Cases | Critical Security Tests |
|--------|-----------------|------------------------|
| Authentication | T01–T07 (7) | T04, T05, T07 |
| Address Management | T08–T11 (4) | T10 |
| Marketplace & Cart | T12–T16 (5) | — |
| Order Placement | T17–T21 (5) | T19 |
| Vendor Order Management | T22–T26 (5) | T24, T26 |
| **Payment Security** | **T27–T35 (9)** | **T28, T29, T30, T31, T32, T33** |
| Pay Later Flow | T36–T41 (6) | T40, T41 |
| Inventory Management | T42–T48 (7) | T43 |
| Dashboard & Revenue | T49–T53 (5) | T50, T51 |
| Notifications | T54–T59 (6) | — |
| **TOTAL** | **59 Test Cases** | **14 Security-Critical** |
