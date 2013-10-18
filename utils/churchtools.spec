# norootforbuild
Name:           churchtools
Version:        2.40
Release:        1
Summary:        Church CRM-like web-based system.
Group:          Productivity/Publishing/Other
License:        MIT
URL:            http://www.churchtools.de/
Source0:        churchtools-2.40.tar.bz2
Requires:       php5
Requires:       php5-mysql
Requires:       mysql
Requires:       apache2
Requires:       apache2-mod_php5
BuildArch:      noarch
BuildRequires:  fdupes
%define ct_dir /srv/www/htdocs/%{name}

%description
ChurchTools offers excellent software tools for CRM tasks in the community and club context.
%prep
%setup -q

%build

%install
mkdir -p %{buildroot}/%{ct_dir}
cp -aRf * %{buildroot}/%{ct_dir}
%if 0%{?suse_version}
%fdupes %{buildroot}%{ct_dir}
%endif

%post

%postun

%files
%defattr(-,wwwrun,www)
%{ct_dir}/*
%dir %{ct_dir}

%changelog

