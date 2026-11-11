# Arthur Amug Osiya — Busia 2027

A responsive Vue 3 campaign website inspired by the spacious structure of kinetics.co.ke. The visual identity uses forest green, warm ivory and muted gold, without a party affiliation.

## Run locally

```sh
npm install
npm run dev
```

Open the Local URL printed by Vite. Create a production build with `npm run build` and serve it with `npm run preview`.

## Verification

```sh
npm run build
npm test
```

The interaction tests mount the production bundle in a simulated DOM. They check navigation, priority tabs and keyboard controls, featured photo controls, gallery filtering and progressive loading, thumbnail navigation, Q&A expansion, focus management, and local interest draft persistence and deletion. They do not replace visual browser checks.

## Content and images

The candidate name was supplied by the user. The five curated photographs served by the website are in `public/images`. All supplied originals have been preserved in `../images/originals`, and excluded website copies are in `../images/not-used`. See `../images/README.md` for the source-to-website mapping and native dimensions. The largest source (960 × 640) leads the hero. Full-frame images and compact portrait sizing reduce enlargement and cropping; no artificial upscaling is used. Higher-resolution originals would further improve sharpness on high-density displays. The proposed priorities and campaign copy are drafts for approval, not assertions of a published manifesto, endorsements, accomplishments or scheduled events. No party is indicated.

The interest form is a working local draft feature. It stores information only in the visitor's browser, supports deletion and text download, and explicitly states that nothing is delivered to the campaign. Connect an approved campaign contact endpoint and privacy policy before collecting live submissions.

Edit page content in `src/App.vue`, the interest form in `src/components/InterestDialog.vue`, and the design in `src/style.css`, `src/enhancements.css`, and `src/hero.css`. Paragraphs use 16px on desktop and 14px on mobile. The full-width hero takes layout cues from teyezillaexpeditions.com: dark background media, a white headline, a gold italic second line, and rounded calls to action. Campaign photographs are capped at their native dimensions and blended into a dark green canvas. It uses photos rather than the reference site’s travel video. The hero carousel supports manual navigation and optional playback; playback pauses on hover, keyboard focus, when a dialog is open, and when the tab is hidden. Five curated gallery images can be filtered and opened in a keyboard-accessible lightbox with thumbnails. Fonts load from Google Fonts with system fallbacks. The layout respects reduced-motion preferences.

The `.openai/hosting.json` file contains the private Sites deployment identity and the static `dist` output configuration.
