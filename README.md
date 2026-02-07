# FAQ_Express
A simple wordpress plugin to show FAQs in a &lt;details> &lt;summary> layout with the option to include the FAQs in LD+JSON schema.

The plugin creates an FAQ custom post type and lets you create any number of FAQs. The FAQ page then lets you add any number of question-answer pairs for the FAQ.

Using this plugin:
- Create a FAQ using the WP Admin sidebar
- Enter the title of the FAQ. In the sidebar of the FAQ editor, you have the option to show this title as a H2 above the FAQ Q/A section.
- Displaying the FAQ is done by shortcode, in the format of [FAQ id="X"]. The shortcode you should use is shown on the FAQ edit screen.
- Add as many Question / Answer sections as you wish. V1 of the plugin does not support rich text.
- Optionally, in the sidebar you can choose to show schema markup for the FAQ.
- Show the shortcode wherever you wish.

Customising the output:
- The questions and answers are displayed in details summary  blocks.
- You can customise the output by either appling css to the wrapper class .faq-list, or by adding your own ID or classes as arguments to the shortcode, for example: [faq id="5" html_id="faq-section" html_name="faq-block" html_class="custom-faq"]
