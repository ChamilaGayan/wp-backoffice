<template>
  <v-app>
    <v-main>
      <div style="padding: 24px;">
        <Login v-if="!userLoggedIn" />
        <Posts v-else />
      </div>
    </v-main>
  </v-app>
</template>

<script>
import Login from './components/Login.vue'
import Posts from './components/Posts.vue'
export default {
  components: { Login, Posts },
  data(){ return { userLoggedIn: false } },
  mounted(){
    fetch('/api/posts', {credentials:'include'}).then(r=>{
      if (r.status === 200) this.userLoggedIn = true;
    }).catch(()=>{ this.userLoggedIn = false });
  }
}
</script>
