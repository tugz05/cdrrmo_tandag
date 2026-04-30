<script setup>
import { onMounted, ref } from 'vue';

const copyrightLabel = ref(null)

onMounted(() => {
    const today = new Date();    
    const currentYear = today.getFullYear();
    copyrightLabel.value = `© ${currentYear} <span">GTSNEMSU</span>`    
})

const legalDocs = [
    {
        route: 'guest.about.index',
        title: 'ABOUT'
    }, {
        route: 'guest.terms-and-conditions.index',
        title: 'TERMS'
    }, {
        route: 'guest.privacy-policy.index',
        title: 'PRIVACY'
    }
]

const nemsuPages = [
    {
        route: 'https://nemsu.edu.ph/',
        icon: 'bi-globe',
    }, {
        route: 'https://www.facebook.com/SDSSUmaintandag',
        icon: 'bi-facebook',
    }
]
</script>
<template>
    <footer class="py-3 my-4 mt-20 px-2">
        <ul class="list-unstyled my-0">
            <li v-for="page in nemsuPages" class="d-inline slide-up-5 me-5">
                <a :href="page.route" 
                    class="link-primary text-decoration-none" 
                    target="_blank">
                    <i class="bi" :class="page.icon"></i>
                </a>
            </li>
        </ul>

        <div class="d-flex justify-content-between align-items-center py-2" style="font-size:xx-small; letter-spacing: 0.08rem;">
            <div class="slide-up-4 text-secondary">
                <span v-html="copyrightLabel"></span>   
            </div>
            <nav class="nav d-flex gap-4">
                <a v-for="legalDoc in legalDocs" 
                    :href="route(legalDoc.route)" 
                    class="nav-item link-secondary" 
                    target="_blank">{{ legalDoc.title }}</a>
            </nav>
        </div>
    </footer>
</template>