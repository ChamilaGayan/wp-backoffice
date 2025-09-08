<template>
  <v-container>
    <v-row>
      <v-col>
        <v-btn @click="refresh">Refresh</v-btn>
        <v-btn @click="showCreate = true">New Post</v-btn>
      </v-col>
    </v-row>

    <v-data-table :items="posts" :headers="headers">
      <template #item.actions="{ item }">
        <v-btn icon @click="edit(item)">edit</v-btn>
        <v-btn icon @click="remove(item)">delete</v-btn>
      </template>
    </v-data-table>

    <!-- create/edit dialogs omitted for brevity -->
  </v-container>
</template>

<script>
import axios from 'axios';
export default {
  data(){ return { posts:[], headers:[{text:'Title',value:'title'},{text:'Status',value:'status'},{text:'Priority',value:'priority'},{text:'Actions',value:'actions',sortable:false}], showCreate:false } },
  methods:{
    async refresh(){ let r = await axios.get('/api/posts'); this.posts = r.data; },
    edit(p){ /* open modal */ },
    async remove(p){ await axios.delete('/api/posts/'+p.id); this.refresh(); }
  },
  mounted(){ this.refresh(); }
}
</script>
