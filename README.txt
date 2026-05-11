Custom Premium Carousel for MetaSlider
======================================

Description:
Integrates a custom premium HTML/CSS/JS active-scaling carousel with the MetaSlider backend data dynamically.

Features:
- Premium scaling animation exactly as provided.
- Infinite looping (cloned slides handled dynamically).
- Works with standard MetaSlider Image attachments, URLs, and Captions.
- Completely separated CSS, JS, and PHP structures.
- Support for multiple instances per page dynamically.
- Upgrade safe: Overrides no core files, operates as shortcode renderer.

Installation:
1. Zip the `custom-premium-carousel` folder.
2. Go to WordPress Dashboard -> Plugins -> Add New -> Upload Plugin.
3. Upload the Zip file and click Activate.

Usage:
Use the following shortcode anywhere on your site, replacing the ID with your actual MetaSlider ID:

[magazine_carousel slider_id="2629"]

Note: 
- Make sure you upload at least 3 slides within the MetaSlider backend to ensure the looping feature works visually. 
- You do NOT need to configure standard MetaSlider display settings (like flexslider vs nivoslider, width/height) as this custom plugin overrides rendering entirely. Just upload images, specify URLs, and text captions inside the MetaSlider dashboard.