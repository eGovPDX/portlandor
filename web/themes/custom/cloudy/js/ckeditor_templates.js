// Override the default template set
CKEDITOR.addTemplates( 'default', {
	// The name of sub folder which hold the shortcut preview images of the
	// templates.  Determine base path of drupal installation if any
	// (ckeditor could possibly be loaded w/o drupalSettings).
	imagesPath: ((drupalSettings && drupalSettings.path) ? drupalSettings.path.baseUrl : '/') + 'themes/custom/cloudy/images/icons/ckeditor_templates/',

	// The templates definitions.
	templates: [ {
		title: 'Vertical spacer - small',
		image: 'template_spacer_sm.gif',
		description: '',
		html: '<div class="spacer-sm cktemplate"></div>'
	}, 
	{
		title: 'Vertical spacer - medium',
		image: 'template_spacer_md.gif',
		description: '',
		html: '<div class="spacer-md cktemplate"></div>'
	}, 
	{
		title: 'Vertical spacer - large',
		image: 'template_spacer_lg.gif',
		description: '',
		html: '<div class="spacer-lg cktemplate"></div>'
	},
	{
		title: 'Phone number',
		image: 'template_phone.gif',
		description: '',
		html: '<a class="btn powr-btn-link" href="tel:"><i class="fas fa-phone"></i>000-000-0000</a>'
	}]
} );
