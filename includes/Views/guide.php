<?php
defined('ABSPATH') || exit;

use Yalogica\HappyVR\Models\DataModel;
?>

<div class="happyvr-guide">
  <div class="happyvr-header">
    <div class="happyvr-title">
      <img width="40" src="<?php echo esc_url(HAPPYVR_PLUGIN_URL . 'assets/img/happyvr.png'); ?>" />
      <span>HappyVR<sup><?php echo sanitize_key(DataModel::getSup()); ?></sup> - </span>
      <span><?php esc_html_e("How To", "happyvr"); ?></span>
    </div>
  </div>
  <div class="happyvr-content">
    <a href="https://yalogica.com/docs/happyvr/?utm_source=plugin&utm_medium=ref&utm_campaign=docs">Documentation</a> | <a href="https://www.youtube.com/playlist?list=PLJOWBe-G-MMM">Video Tutorials</a> 
    <h2>How to create a virtual tour</h2>
    <ol>
      <li>In the WordPress admin sidebar, go to "HappyVR" > "All Virtual Tours"</li>
      <li>Click the "Add New" button.</li>
      <li>Enter a title for your virtual tour in the title field.</li>
      <li>Upload your 360 panorama or high-resolution images for each scene.</li>
      <li>Add markers onto the visual canvas.</li>
      <li>Customize markers by adding specific icons, tooltips, popups, or actions.</li>
      <li>Optional: upload background music for the entire tour or individual scenes.</li>
      <li>Click "Save" to store the virtual tour and "Publish" to make it available for use. It is now ready to be embedded anywhere on your site.</li>
    </ol>

    <h2>Publish with a shortcode</h2>
    <ol>
      <li>On the All Virtual Tours list, locate the "Shortcode" column and copy the code for your virtual tour, e.g. [happyvr id="123"].</li>
      <li>Paste the shortcode into any post, page, or widget area that supports shortcodes.</li>
    </ol>

    <p>Shortcode attributes:</p>
    <ul>
      <li><code>id</code> - the ID of the virtual tour (the virtual tour should have a “publish” state)</li>
      <li><code>width</code> - any valid CSS value</li>
      <li><code>height</code> - any valid CSS value</li>
      <li><code>class</code> - CSS classes separated by spaces for styling the virtual tour container</li>
      <li><code>sceneid</code> - the ID of the scene you want to load (if you want to override the default scene defined in the config file)</li>
    </ul>
  </div>
</div>