const target = db.getSiblingDB(process.env.MONGO_DATABASE || 'tienda');
if (!target.getUser('tienda_app')) {
  target.createUser({user:'tienda_app',pwd:process.env.MONGO_APP_PASSWORD,roles:[{role:'readWrite',db:target.getName()}]});
}
