# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  # All Vagrant configuration is done here. The most common configuration
  # options are documented and commented below. For a complete reference,
  # please see the online documentation at vagrantup.com.

  # Every Vagrant virtual environment requires a box to build off of.
  config.vm.box = "sagepe/wheezy"

  # Create a forwarded port mapping which allows access to a specific port
  # within the machine from a port on the host machine. In the example below,
  # accessing "localhost:8080" will access port 80 on the guest machine.
  config.vm.network "forwarded_port", guest: 80, host: 8080

  config.vm.provision :shell, :inline => <<-EOS
    # To prevent "dpkg-preconfigure: unable to re-open stdin: No such file or directory" warnings
    export DEBIAN_FRONTEND=noninteractive
    sh /vagrant/bin/vagrant-install.sh
  EOS

  # It's likely the shared folder wasn't available when Apache started.
  # So restart Apache again once the machine has started up.
  config.vm.provision :shell, :run => "always", :inline => <<-EOS
    sudo service apache2 restart
    echo "Petitions is up and running, you can view it at http://petitions.127.0.0.1.xip.io:8080/"
  EOS
end
