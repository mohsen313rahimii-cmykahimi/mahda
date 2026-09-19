self.addEventListener('install',()=>self.skipWaiting());
self.addEventListener('activate',event=>event.waitUntil((async()=>{
  try{const names=await caches.keys();await Promise.all(names.map(n=>caches.delete(n)));}catch(e){}
  try{await self.registration.unregister();}catch(e){}
  try{const clients=await self.clients.matchAll({type:'window',includeUncontrolled:true});for(const client of clients){client.navigate(client.url);}}catch(e){}
})()));
self.addEventListener('fetch',()=>{});
