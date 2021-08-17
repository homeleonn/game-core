<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Vue</title>
	<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
</head>
<body>
	
	<div id="app">
		<h1>{{ vueH1 }}</h1>
		<h2>new notes</h2>
		<input type="text" :value="note" @input="inputNote"> <button @click="addNote">add note</button>
		<ul>
			<li v-for="(value, key) in notes">
				[{{ key }}]: {{ value }}
			</li>
		</ul>

		<h2>Vue object</h2>
		<ul>
			<li v-for="(value, key, index) in _data">
				[{{ index }}.{{ key }}]: {{ value }}
			</li>
		</ul>
	</div>

	<script>
		let app = new Vue({
			el: '#app',
			data() {
				return {
					vueH1: 'Hello, Vue!',
					notes: [],
					note: ''
				}
			},
			methods: {
				inputNote(e) {
					this.note = e.target.value;
				},

				addNote() {
					this.notes.push(this.note);
					this.note = '';
				}
			}
		});
	</script>
</body>
</html>