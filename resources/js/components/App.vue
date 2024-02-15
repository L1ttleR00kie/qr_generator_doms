<template>
    <main>
        <div id="app">

            <h1>{{ message }}</h1>

            <button @click="reverseMessage">Reverse Message</button>

            <button @click="message += '!'">Append "!"</button>

            <a href="https://vuejs.org" @click.prevent="notify">
                A link with e.preventDefault()
            </a>

            
            <div id="app">
                <button @click="count++">
                    Count is: {{ count }}
                </button>
            </div>

            <form id="search">
                Search <input name="query" v-model="searchQuery">
            </form>
            <DemoGrid
                :data="gridData"
                :columns="gridColumns"
                :filter-key="searchQuery">
            </DemoGrid>

            
        
        </div>
    </main>
</template>

<script setup>
    import { createApp, ref } from 'vue'
    import '../../css/app.css'

    const count = ref(0)

    const message = ref('Hello World!')

    function reverseMessage() {
    message.value = message.value.split('').reverse().join('')
        }
    function notify() {
        alert('navigation was prevented.')
        }

    import DemoGrid from './Grid.vue'

    const searchQuery = ref('')
    const gridColumns = ['name', 'power']
    const gridData = [
    { name: 'Chuck Norris', power: Infinity },
    { name: 'Bruce Lee', power: 9000 },
    { name: 'Jackie Chan', power: 7000 },
    { name: 'Jet Li', power: 8000 }
    ]


</script>

<style scoped>
    button, a {
    font-weight: bold;
    font-size: 25px;
    display: block;
    margin-bottom: 1em;
    }
</style>


