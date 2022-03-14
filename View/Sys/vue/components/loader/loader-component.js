if(typeof window['_vue'] == 'undefined'){
	window['_vue'] = {};
}

class loaderComponent {
	isLoading = false;
	text = 'Loading...';

	reset(){
		this.isLoading = false;
		this.text = 'Loading...';
	}
}

window['_vue']['loader-component'] = new loaderComponent();

Vue.component('loader-component', {
	data: function(){
		return {
			vue: window['_vue']['loader-component']
		}
	},
	template: '{{template}}'
});