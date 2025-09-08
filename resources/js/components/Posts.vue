<template>
  <v-container>
    <v-row class="mb-4">
      <v-col cols="8">
        <v-btn @click="refresh">Refresh</v-btn>
        <v-btn @click="openCreate">New Post</v-btn>
        <v-btn @click="sortByPriority">Sort by Priority</v-btn>
      </v-col>
    </v-row>

    <v-data-table :items="posts" :headers="headers">
      <template #item.priority="{ item }">
        <v-text-field type="number" :value="item.priority" @change="setPriority(item,$event)" dense solo></v-text-field>
      </template>
      <template #item.actions="{ item }">
        <v-btn small @click="openEdit(item)">Edit</v-btn>
        <v-btn small color="error" @click="remove(item)">Delete</v-btn>
      </template>
    </v-data-table>

    <!-- Create/Edit simple dialog -->
    <v-dialog v-model="dialog" width="600">
      <v-card>
        <v-card-title>{{ editing ? 'Edit Post' : 'New Post' }}</v-card-title>
        <v-card-text>
          <v-text-field v-model="form.title" label="Title"></v-text-field>
          <v-textarea v-model="form.content" label="Content"></v-textarea>
          <v-select v-model="form.status" :items="['draft','publish']" label="Status"></v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="dialog=false">Cancel</v-btn>
          <v-btn @click="save">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import axios from 'axios';
export default {
  data(){ return {
    posts: [],
    headers: [
      { text: 'Title', value: 'title' },
      { text: 'Status', value: 'status' },
      { text: 'Priority', value: 'priority' },
      { text: 'Actions', value: 'actions', sortable:false }
    ],
    dialog:false, editing:false,
    form:{ id:null, title:'', content:'', status:'draft'}
  }},
  methods:{
    async refresh(){ const r = await axios.get('/api/posts'); this.posts = r.data; },
    openCreate(){ this.editing=false; this.form = {id:null,title:'',content:'',status:'draft'}; this.dialog=true; },
    openEdit(item){ this.editing=true; this.form = {id:item.id,title:item.title,content:item.content,status:item.status}; this.dialog=true; },
    async save(){
      if(this.editing){ await axios.put(`/api/posts/${this.form.id}`, this.form); }
      else { await axios.post('/api/posts', this.form); }
      this.dialog=false; await this.refresh();
    },
    async remove(item){ await axios.delete(`/api/posts/${item.id}`); await this.refresh(); },
    async setPriority(item,event){ const value = parseInt(event.target ? event.target.value : event); await axios.post(`/api/posts/${item.id}/priority`, {priority:value}); await this.refresh(); },
    sortByPriority(){ this.posts.sort((a,b)=>b.priority - a.priority); }
  },
  mounted(){ this.refresh(); }
}
</script>
