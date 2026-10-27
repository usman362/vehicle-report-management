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

## Uploading a report

Before the file can be stored you answer, for every source published on the
data-sources page, one of three things: a record was found, the source was
checked and holds nothing for this vehicle, or it could not be checked. The
last one has to say why, and the customer is told that reason in their email.

"No record for this vehicle" and "could not be checked" are not
interchangeable. The first is a finding. The second is a gap, and the email
says so in as many words — a gap read as a pass is the one mistake this
service cannot afford.

## Refunding an order

Refund in WooCommerce, not only in the payment provider's dashboard. A full
refund through WooCommerce revokes the customer's download links at the same
time; a refund made elsewhere does not reach the site and leaves their link
working.

After a full refund the resend button refuses, with a reason, and the refusal
is written to the audit log. A partial refund leaves everything reachable,
which is the point of a partial refund.

## Backups

`scripts/backup.sh` saves the database, the uploads and the private report
directory in one go; `scripts/restore.sh` puts all three back and asks before
it does. Both are described in `docs/DEPLOYMENT.md`.

Check once a month that the backups are still being written and are the size
you expect, and rehearse a restore on staging at least twice a year. A restore
counts as proven only when a download link issued before the backup returns the
report afterwards — that single check covers the database, the files and the
signing secret in one go.

## After a WordPress, WooCommerce or plugin update

Run `./scripts/check.sh` on a copy of the site. Step ④ exercises the parts that
quietly stop working after an update: whether a support agent is still refused
when they try to deliver, whether a failed delivery still queues a retry, and
whether two orders for the same registration still have separate links. It
cleans up after itself, so it is safe to run on staging.

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
