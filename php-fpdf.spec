%global php_libname          fpdf
Name:      php-fpdf
Version:   1.7
Release:   14%{?dist}
License:   MIT
Summary:   PHP class to generate PDF Files 
Group:     Development/Libraries
URL:       http://www.fpdf.org
# Upstream uses a troublesome URL for source.  Greb it from the Github reference below.

Source:    https://github.com/Setasign/FPDF/archive/%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
Requires:  php-gd
BuildRequires:  dos2unix
BuildArch: noarch

%description
FPDF is a PHP class which allows to generate PDF files with pure PHP, that is 
to say without using the PDFlib library. F from FPDF stands for Free: you may 
use it for any kind of usage and modify it to suit your needs.

%package doc
Summary: Documentation for php-fpdf
Group:   Development/Libraries
%description doc
Documentation for php-fpdf 


%prep
%setup -qn FPDF-%{version}
dos2unix doc/*
dos2unix *.txt
find -type f | xargs chmod -x 
dos2unix tutorial/*
dos2unix fpdf.php
pushd tutorial
for file in calligra.z 20k_c1.txt; do
   iconv -f ISO-8859-1 -t UTF-8 -o $file.new $file && \
       touch -r $file $file.new && \
   mv $file.new $file
done
popd
%build
%install
rm -rf %{buildroot}

mkdir -p %{buildroot}%{_datadir}/php/%{php_libname}
cp -a font fpdf.php  %{buildroot}%{_datadir}/php/%{php_libname}

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
%{_datadir}/php/%{php_libname}
%doc license.txt

%files doc
%defattr(-,root,root,-)
%doc FAQ.htm doc changelog.htm install.txt license.txt tutorial


%changelog
* Sat Oct 10 2020 Bishop <bishopolis@gmail.com> - 1.7-14
- Fixed URL in spec for github upstream

* Sat Feb 11 2017 Fedora Release Engineering <releng@fedoraproject.org> - 1.6-13
- Rebuilt for https://fedoraproject.org/wiki/Fedora_26_Mass_Rebuild

* Thu Feb 04 2016 Fedora Release Engineering <releng@fedoraproject.org> - 1.6-12
- Rebuilt for https://fedoraproject.org/wiki/Fedora_24_Mass_Rebuild

* Thu Jun 18 2015 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.6-11
- Rebuilt for https://fedoraproject.org/wiki/Fedora_23_Mass_Rebuild

* Sat Jun 07 2014 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.6-10
- Rebuilt for https://fedoraproject.org/wiki/Fedora_21_Mass_Rebuild

* Sun Aug 04 2013 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.6-9
- Rebuilt for https://fedoraproject.org/wiki/Fedora_20_Mass_Rebuild

* Thu Feb 14 2013 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.6-8
- Rebuilt for https://fedoraproject.org/wiki/Fedora_19_Mass_Rebuild

* Fri Jul 20 2012 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.6-7
- Rebuilt for https://fedoraproject.org/wiki/Fedora_18_Mass_Rebuild

* Sat Jan 14 2012 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.6-6
- Rebuilt for https://fedoraproject.org/wiki/Fedora_17_Mass_Rebuild

* Wed Feb 09 2011 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.6-5
- Rebuilt for https://fedoraproject.org/wiki/Fedora_15_Mass_Rebuild

* Sun Sep 05 2010 David Nalley <david@gnsa.us> 1.6-4
- updated file so license.txt is included in both doc subpackage and main package. 
* Sun Jan 03 2010 David Nalley <david@gnsa.us> 1.6-3
- updated requires to include php-gd 
* Sat Nov 28 2009 David Nalley <david@gnsa.us> 1.6-2
- updated source url to note troublesome link
- consolidated things to be a single line for all of the chmods
- additional command consolidation based on Thomas Spura's comments
- broke documentation out into a separate package
* Thu Nov 26 2009 David Nalley <david@gnsa.us> 1.6-1
- Initial Packaging
