# Sprint 1: "Start as a freelancer / sole trader"

## Which scenario, and how sure we are
Mitos holds 4,408 published procedures. I counted by title keyword: ~700 licences and permits, ~160 business start/close, ~150 social security, ~100 residence/immigration, ~60 staff/employment, ~60 vehicles, ~30 tax certificates.

Caveat: this shows what exists, not what people need. I found no per-procedure demand data (gov.gr publishes only totals). The only hard demand signal is business formation: GEMI recorded 45,038 business starts in Jan–Jul 2025 (+6.5% on 2024), with ~1M active entities at end of 2025 and new ones mostly sole proprietorships and private companies (IKE). Starting a business is a one-off, multi-agency, order-dependent journey, which is the case a checklist helps most.

Licences are the largest group but fragment by sector (security firms, private investigators...), so no single bundle serves many people. Start-up is the best first bet, but it is a bet: validate with real users in week 2.

## Procedures in the bundle (found in Mitos)
| Step | Mitos code | Title |
|---|---|---|
| Get a tax number (only if you don't have one) | 557869 | Απόδοση ΑΦΜ & Κλειδαρίθμου |
| Start the business | 113509 | Έναρξη ατομικής επιχείρησης |
| Social security (self-employed) | 185609 | Απογραφή μη μισθωτών ασφαλισμένων, ελεύθερων επαγγελματιών και αυτοαπασχολούμενων |
| E-invoicing | 963556 | Εγγραφή στην Ψηφιακή Πλατφόρμα myDATA |
| Proof of tax standing | 439993 | Αποδεικτικό φορολογικής ενημερότητας |
| Proof of insurance standing | 119372 | Ασφαλιστική ενημερότητα φυσικών / νομικών προσώπων (eΕΦΚΑ) |
| Close the business (later) | 643276 | Διακοπή εργασιών φυσικών και νομικών προσώπων |
| AMKA (if missing) | 791797 | Έκδοση ΑΜΚΑ σε Έλληνες πολίτες και ομογενείς (ενήλικοι) |

## Sequence: what official sources establish (checked 2026-10-08)
1. **Tax start first.** Mitos 113509 (commercial sole proprietorship, online via myAADE, ~12 min, free, no documents) needs TAXISnet codes and registers the start with AADE and ΓΕΜΗ in one go. You choose the ΚΑΔ (activity codes) here. Output: the "βεβαίωση έναρξης εργασιών" in myAADE. The AADE guide (aade.gr, "Έναρξη επιχειρηματικής δραστηριότητας") covers the tax side step by step.
2. **Then e-EFKA registration.** Mitos 185609 states it applies to people who have started activity with the tax authority and are insured for the first time, requires an application (online with TAXISnet, or in person), and must be filed **by the 10th of the month after the tax start**. Pre-registration is no longer required. If you don't apply, e-EFKA can register you ex officio and contributions accrue from the start. Legal basis: ν. 4892/2022 art. 22, circular 14/2022. The online flow uses KAD codes from step 1 and matches your AMKA.
3. **AMKA** is matched in the e-EFKA flow; the Mitos page does not list it as a document. Treat "get an AMKA" as a prerequisite to verify, not a certain step.
4. **myDATA / invoicing** comes after the start. Mitos 963556 should be read for its own conditions; the official order relative to the start is not yet confirmed.

Still unconfirmed from official text: (a) myDATA timing, (b) what applies to non-commercial freelancers (113509 covers *commercial* activity, Greek or EU adults, permanent residents; other cases go through the tax office/ΓΕΜΗ), (c) third-country nationals. A short accountant check is still sensible for these, not for the core order above.

## Work items
1. **Bundle (journey) model.** An ordered list of procedure codes with per-step notes ("do this first", "needs X from step 1"), stored in the plugin, edited in admin. Shortcode `[mitos_journey id="freelancer"]`. Combined progress across all steps.
2. **Guided questions.** Three at most, shown before the journey: Greek citizen or not, already have AFM/AMKA, planning to employ staff. Answers hide or add steps (e.g. skip AMKA if held). Store the answers on the device.
3. **Calendar reminders.** Per-step "remind me" that downloads an .ics file (no accounts). Include the legal deadline note where the registry states one.
4. **"Where to get it" for documents.** Map the ~10 most common document types in these procedures to their issuing service and link them.
5. **Alternatives shown as "any one of".** Group documents the registry marks as alternatives.
6. **Reviewed flag per step**, so the journey page shows what is verified and what is not.
7. **Change warning.** If the registry's last-updated date moves after the user began, show "this procedure changed on <date>".

## Status
Done: 1 (journey model), 2 (questions), 3 (.ics reminders), 4 (document sources via Mitos procedures), 5 (alternatives grouped), 7 (change warning). Item 6 (reviewed flag) shows as a tick on each step. Greek is the default language for visitors (`mitoschk_language` filter returns `en` for English).

## Out of scope
Accounts, uploads, AI summaries, eligibility verdicts, accountant features.

## Validation (week 2)
Give 5–8 people who are about to start a business the journey page. Measure: do they finish the preparation without asking someone? Which steps do they open most? What did they still have to ask an accountant? If few finish, or nobody is about to start, switch scenario (candidates: employing a first person, moving business address).

## Open question
Who can check items (a)–(c) above and the document sources? The core order (tax start → e-EFKA by the 10th of next month) no longer needs an accountant.
