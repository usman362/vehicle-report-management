# Operations manual

For the people who prepare and send reports. No code knowledge needed.

## The daily job

1. Open **Vehicle Reports → Fulfilment queue**. It opens on outstanding work,
   oldest first, with the time left against the promise.
2. Open an order, press **Assign to me**, then **Mark in progress**.
3. Research the vehicle using the sources the business is licensed to use.
4. Write the report. Say what each source showed, and mark anything that could
   not be checked as **not checked** — never as clear.
5. Back on the order, upload the PDF with:
   - **Sources used** — exactly which ones, and the date you checked them.
   - **What could not be checked** — in plain words.
6. Press **Deliver to customer**. Only now is the customer told anything.

Uploading is not delivering. A stored report stays private until someone
presses Deliver.

## When something is wrong

**No registration on the order.** The order sits in *Needs customer
information*. Email the customer, add the mark to the order, then continue.

**The registration is wrong or the vehicle cannot be identified.** Contact the
customer first. If it cannot be resolved, use **Mark as cannot fulfil** with a
reason the customer can be told, and arrange a refund.

**A source has no record.** That is a result, not a failure. Say so in the
report. Never imply a clear history from missing data.

**The delivery email failed.** The order shows *Delivery failed*. The system
retries with increasing gaps and then emails support. The file is safe; use
**Resend link** once the mail problem is fixed.

**The customer says the link expired.** Expected — links are time limited.
Press **Resend link** for a fresh one.

**A link may have been shared.** Press **Revoke links**, then **Resend link**.
Everything sent before stops working immediately.

**A report was wrong and needs replacing.** Upload the corrected file. The old
version is kept with its hash, the new one becomes current, then press Deliver
again and tell the customer what changed.

## What staff must never do

- Promise a check the business has no source for.
- Record a vehicle as clear when the data was simply unavailable.
- Send a report prepared for one customer to a different customer.
- Email a report file as an attachment outside the system; use the link.
- Share a download link in a chat, ticket or anywhere it can be forwarded.
- Edit a past order's price, terms version or audit history.

## Roles

| Role | Can |
|---|---|
| Report fulfilment staff | See the queue, claim orders, upload, deliver |
| Report support agent | See the queue, resend a link |
| Report store manager | Everything above, plus settings and the audit log |

Nobody needs to be a WordPress administrator to prepare or send a report.

## The audit log

**Vehicle Reports → Audit log** records every upload, delivery, resend,
revocation, refused download and failed permission check. Entries cannot be
edited or deleted, and sensitive values are redacted before they are stored.
It is the first place to look when a customer disputes something.

## Changing page wording

The storefront and legal pages are files in `content/pages/`, not just rows in
the database. Edit the file, commit it, and run `wp eval-file
scripts/seed-content.php` on the server. Editing a page in the WordPress editor
instead works until the next deployment overwrites it.

The terms, privacy, cookie and refund pages ship as drafts and say so on the
page. Clear that notice only by ticking **Vehicle Reports → Settings → Legal
pages → Copy reviewed**, and only once a legal adviser has actually settled the
wording. Untick it and the notice comes back everywhere at once.

## Changing the price or the delivery window

Change them in **Vehicle Reports → Settings**. Orders already placed keep the
price, delivery window and terms version that applied when they were placed —
that snapshot is what the customer agreed to.
