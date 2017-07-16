# Optimized for Vagrant 1.7 and above.
Vagrant.require_version ">= 1.7.0"

Vagrant.configure(2) do |config|
    config.vm.box = "ubuntu/xenial64"

    config.vm.hostname = "yii2-couchbase"
    config.vm.network "private_network", ip: "192.168.33.77"

    config.vm.network "forwarded_port", guest: 8091, host: 8091

    config.vm.synced_folder ".", "/vagrant", type: "virtualbox"
    
    config.vm.provision "shell", path: "vagrant/provision.sh"

    config.vm.provider "virtualbox" do |vb|
        vb.name = "yii2-couchbase"
        vb.gui = false
        vb.memory = "4096"
        vb.cpus = "2"
    end
end