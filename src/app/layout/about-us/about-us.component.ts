import { Component, OnInit, HostListener } from '@angular/core';
import { routerTransition } from '../../router.animations';
import { Meta, Title } from '@angular/platform-browser';

interface Card {
  id: string;
  title: string;
  icon: string;
  description: string;
  image: string;
  isVisible: boolean;
}

@Component({
  selector: 'app-about-us',
  templateUrl: './about-us.component.html',
  styleUrls: ['./about-us.component.scss'],
  animations: [routerTransition()]
})
export class AboutUsComponent implements OnInit {

  featureCards: Card[] = [
    {
      id: 'expertise',
      title: 'Our Expertise',
      icon: 'fa-industry',
      description: 'Incorporated in 1998, at Mumbai, Maharashtra, Om Industries is a leading manufacturer and supplier of highly efficient and compact transformers. Our product range includes PCB Mountable Transformer, Voltage Stabilizer Transformer, Transformer For UPS, AC DC Adaptor Transformers, Pulse Transformers, Inverters Transformer, Electrical Chokes & Coil.',
      image: 'assets/images/about-us/expertise-bg.jpg',
      isVisible: false
    },
    {
      id: 'quality',
      title: 'Quality & Innovation',
      icon: 'fa-cogs',
      description: 'Manufactured from high grade raw materials using advanced technology, our transformers are appreciated for efficiency, design, insulation, functionality, low maintenance, performance, heat resistance, and voltage stability. We ensure quality by sourcing components from reliable suppliers and adhering to industry standards.',
      image: 'assets/images/about-us/quality-bg.jpg',
      isVisible: false
    },
    {
      id: 'team',
      title: 'Our Team',
      icon: 'fa-users',
      description: 'Our infrastructure is divided into R&D, manufacturing, warehousing, polishing, and quality control. Highly qualified professionals manage each unit, working together to deliver flawless, long-lasting transformers. Rigorous testing ensures defect-free products.',
      image: 'assets/images/about-us/team-bg.jpg',
      isVisible: false
    },
    {
      id: 'satisfaction',
      title: 'Client Satisfaction',
      icon: 'fa-thumbs-up',
      description: 'We consistently deliver quality products, perfect packaging, and hassle-free deliveries. Transparent policies and a commitment to excellence have helped us build strong relationships and a loyal clientele nationwide.',
      image: 'assets/images/about-us/satisfaction-bg.jpg',
      isVisible: false
    }
  ];

  constructor(private meta: Meta, private title: Title) { }

  ngOnInit(): void {
    this.title.setTitle('OM Industries | Industrial Solutions');
    this.meta.addTags([
      { name: 'description', content: 'OM Industries provides premium industrial solutions...' },
      { name: 'keywords', content: 'industrial, manufacturer, OM, products' },
      { property: 'og:title', content: 'OM Industries' },
      { property: 'og:description', content: 'Manufacturer of all type of transformers' },
      { property: 'og:url', content: 'https://omindus.com' },
    ]);
    this.observeCardVisibility();
  }

  private observeCardVisibility(): void {
    if (typeof IntersectionObserver !== 'undefined') {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const cardId = entry.target.getAttribute('data-card-id');
            const card = this.featureCards.find(c => c.id === cardId);
            if (card) {
              card.isVisible = true;
              observer.unobserve(entry.target);
            }
          }
        });
      }, {
        threshold: 0.2,
        rootMargin: '0px 0px -100px 0px'
      });

      setTimeout(() => {
        document.querySelectorAll('[data-card-id]').forEach(el => {
          observer.observe(el);
        });
      }, 100);
    }
  }

  @HostListener('window:scroll', ['$event'])
  onWindowScroll(): void {
    // Additional scroll effects can be added here
  }

}

