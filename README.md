# Mitos Checklist

A WordPress plugin that turns official Greek administrative procedures from the [Mitos registry](https://mitos.gov.gr) into preparation checklists on your site.

- Pick up to 50 procedures by registration code (MAK) in **Settings → Mitos Checklist**.
- Data is fetched from the Mitos API and refreshed daily; the front end shows when.
- Visitors search procedures, tick off documents and see what is still missing, with progress and private notes saved on their own device (no accounts, nothing sent to your server).
- Every checklist links to the original Mitos procedure and the official service.
- Tick "Reviewed" after you verify a procedure; visitors then see a review date.
- Greek or English interface by site language.

Embed a guided multi-step journey with `[mitos_journey id="freelancer"]` (start as a sole trader: asks three questions, hides steps you don't need, tracks progress, computes the e-EFKA deadline from your start date and offers a calendar reminder). Journeys are defined in `includes/class-journeys.php` and the `mitoschk_journeys` filter; their procedures sync automatically.

Embed with `[mitos_checklist]` (searchable list) or `[mitos_checklist id="439993"]` (one procedure).

## Data licence
Procedure content comes from Mitos and is licensed CC BY-SA 4.0. The plugin shows the credit on every checklist. The Mitos API is marked as a trial service and may change or add authentication; check api@mitos.gov.gr.

## Develop
```
npx @wp-playground/cli@latest server --blueprint=dev/blueprint.json --mount="$PWD:/wordpress/wp-content/plugins/mitos-checklist" --workers=1
```

Informational only: always confirm on the official service before applying.
